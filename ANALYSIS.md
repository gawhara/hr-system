# HR System — Technical Analysis

**Date:** 2026-07-06 · **Scope:** architecture, security, performance, PRD gap analysis
**Stack:** Laravel 12, PHP 8.2 (XAMPP), MySQL/MariaDB, Blade + Tailwind/Vite, spatie/permission, spatie/activitylog, openspout, zkteco-php

---

## 1. Overview

Arabic-first, offline-ready HR ERP for a Saudi multi-company group. ~4,200 lines of application PHP across 17 controllers, 27 models, 7 services, 27 migrations, and 16 feature test files (83 tests). Modules: employees, contracts, documents + expiry alerts, leave, attendance (incl. ZKTeco biometric pull), payroll with workflow + payslips + Mudad/WPS export, GOSI & Nitaqat calculators, branch↔central sync, dashboards/reports.

**Overall verdict:** a well-structured, unusually well-documented codebase for its stage. Domain decisions (encryption, sync conflicts, payroll locking) are deliberate and recorded in AGENT.md/README. The main risks are concentrated in the sync API trust model, missing login hardening, an under-implemented role matrix, and a denormalized Employee god-model.

---

## 2. Architecture

### Strengths
- Clean separation: thin controllers, services for GOSI/Nitaqat/sync/biometric, `Syncable` trait (uuid + synced_at) applied consistently to offline-writable tables.
- PII handled maturely: `national_id`/`iban`/`passport_id` encrypted at rest, deterministic SHA-256 hash column for uniqueness/exact search, PII masked before entering the activity log (`tapActivity`).
- Payroll workflow is a real state machine (draft → under_review → approved → locked) with adjustment runs linked to locked cycles, and a sync-completeness gate before locking.
- Strong feature-test coverage of authorization paths (cross-company access, employee vs HR roles, payroll immutability).
- Bilingual/RTL done properly; no CDN dependencies (offline constraint respected).

### Weaknesses
1. **No Policy classes.** Authorization is ad-hoc `abort_unless(...)` scattered per method. It's currently correct (verified across controllers), but every new endpoint must remember 2–3 checks manually. Move to Policies + `authorizeResource`.
2. **Dual role system.** Legacy `role` column + spatie roles bridged by `LEGACY_ROLE_MAP`. Two sources of truth; `isHrAdmin()` checks both. Consolidate on spatie and drop the column.
3. **Employee god-model.** ~70 columns including per-bank transfer amounts (`al_rajhi_transfer`, `riyad_bank_transfer`…) and payroll deductions duplicated from the spreadsheet import — the same figures also live in `payroll_items`. The PRD (§6) specifies normalized tables (`employee_salaries`, `employee_salary_deductions`, `employee_payment_methods`). Current shape works for spreadsheet parity but two sources of payroll truth will drift.
4. **Duplicate field pairs** (`name_ar` vs `full_name_arabic`, `gosi_basic_salary` vs `basic_salary_gosi`) with no documented canonical one.
5. **`$guarded = []`** on models. Safe today only because controllers pass `$request->validated()`; one careless `->update($request->all())` away from mass assignment. Prefer explicit `$fillable`.
6. **Hardcoded branding logic** (`companyLogo()`/`companyTheme()` match on company names) belongs in DB columns.
7. **Performance module is a static mock** — the view renders hardcoded data and a dead button.

---

## 3. Security review

### High
| # | Finding | Detail |
|---|---------|--------|
| S1 | **No login rate limiting** | `AuthController@store` has no `throttle` middleware or lockout. Combined with seeded `password` demo accounts, brute-force is trivial on a LAN. Add `throttle:5,1` / `RateLimiter`, rotate all seeded passwords, add password reset + optional 2FA for admins. |
| S2 | **Flat sync trust model** | One shared static bearer token for all branches (`HR_SYNC_TOKEN`). Any token holder can `GET /api/sync/pull` the *entire* dataset (all employees, salaries, PII ciphertext) and push writes. No branch identity, no scoping, no replay protection, no TLS enforcement. Recommend per-branch tokens (Sanctum), branch-scoped pull, and HTTPS-only. |
| S3 | **`SyncApplier` writes raw attributes** | `setRawAttributes($attributes)` applies client-supplied fields with no per-column allowlist, bypassing validation and model events. A compromised branch token can set any column on synced tables (salaries, GOSI wage, status). Add a per-type writable-field allowlist mirroring `SyncRegistry`. |

### Medium
| # | Finding | Detail |
|---|---------|--------|
| S4 | **Deployment config is dev-grade** | `.env`: `APP_DEBUG=true`, MySQL `root` with no password, `SESSION_ENCRYPT=false`. Fine for local dev; `start-system.bat` suggests this same setup will run at branches — harden before that. `.env` correctly excluded from git. |
| S5 | **Mudad export writes decrypted PII to CSV** | national_id + IBAN in plaintext file. Required for WPS, and it *is* audited — but there's no download expiry/handling policy. Also the column format is self-flagged as unverified against the official Mudad spec. |
| S6 | **Leave approval trusts the request blindly** | No overlap check between requests, no balance-sufficiency check (`used_days` can exceed `entitled_days` silently), `days` = calendar days incl. weekends/holidays, and cross-year leaves attribute all days to the start year. |
| S7 | **No pagination cursor on sync pull** | `limit(500)` per model with `updated_at > since`; a burst of >500 changes (e.g. bulk import) silently drops records, and identical-timestamp rows at the boundary can be skipped. Return a per-type cursor / use `>=` with id tiebreaker. |
| S8 | **Unvalidated `date` param** | `AttendanceController` passes `?date=` straight into `whereDate()` → 500 on malformed input. Validate format. |

### Good practice observed
CSRF on by default; no raw `{!! !!}` output in any Blade view (XSS-clean); all queries through Eloquent bindings (no SQLi found); document uploads restricted to pdf/jpg/png ≤5 MB on the private disk with proper download authorization; `hash_equals` for token comparison; sensitive exports and status changes audited; payslip self-service correctly limited to locked runs.

---

## 4. Performance review

Generally healthy at current scale — eager loading is consistent (no N+1 found in list views), pagination everywhere, and the core migration defines sensible composite indexes (`company_id+status`, `employee_id+work_date` unique, expiry-date index). Spreadsheet export streams via `->each()` (chunked). Specific items:

1. **Dashboard/report aggregate mislabeled and unbounded** — see bug B1 below; cost grows with every payroll cycle ever created.
2. **Dashboard fires ~8 count queries per company** per page view, uncached. Fine at 4 companies; add a short TTL cache (`Cache::remember`, 5–15 min) as headcount queries grow.
3. **`whereDate('work_date', …)`** wraps the column in `DATE()`, defeating the index. `work_date` is already a date column — use `where('work_date', $date)`.
4. **`LIKE '%…%'` search** on names can't use indexes; acceptable now, consider a FULLTEXT index past ~50k employees. Also `%`/`_` in user input aren't escaped (filter oddity, not a vulnerability).
5. **`profileCompletion()` runs on every save** — attribute-only math, cheap; the relation-loading variant is correctly separated (`recomputeProfileCompletionQuietly`). No action needed.

---

## 5. Correctness bugs

| # | Bug | Location |
|---|-----|----------|
| B1 | `monthly_payroll` (dashboard) and `payroll` metric (reports) sum `net_salary` across **all payroll items ever**, not the current month/cycle. Numbers inflate each month. | `DashboardController::companyDashboard`, `ReportsController` |
| B2 | Leave approval can drive balances negative with no warning; no overlap detection. | `LeaveRequestController::approve/store` |
| B3 | Leave `days` counts calendar days (weekends/holidays included). | `LeaveRequestController::store` |
| B4 | `EMPLOYED_STATUSES` (Nitaqat/headcount counting) is a self-flagged placeholder — verify suspended employees against official GOSI/Nitaqat rules. | `Employee` model |
| B5 | Last recorded PHPUnit run left ~38 entries in the `.phpunit.result.cache` defects bucket (likely deprecations/risky). Re-run `php artisan test` and check it's actually green. | — |

---

## 6. Feature/gap analysis vs PRD (Employee Module, Bayzat reference)

### Implemented ✔
- **FR-001** list + search + filters + pagination + data-quality chips (probation / expired iqama / incomplete)
- **FR-002/003** create & edit with the full bilingual field set, company/branch/department consistency validation, min-age rule (§7.2)
- **FR-004** disable/status change with mandatory reason + status history
- **FR-006** documents with types, expiry tracking, scheduled expiry alerts (in-app + optional mail)
- **FR-007/008** import/export native .xlsx + CSV fallback + downloadable template, audited
- **§8** profile completion % with per-section breakdown; **§12** unified status vocabulary; activity log with PII masking
- Profile tabs: overview, personal, work, contract/salary, deductions, payment methods, documents, attendance/leaves, activity — all present

### Missing / partial ✘
| PRD item | Status |
|----------|--------|
| **FR-005** Delete employee (Super Admin soft-delete) | `SoftDeletes` trait exists but there is **no destroy route or UI** — unreachable feature |
| **§3 role matrix** (HR Officer, Accountant, Branch Manager, Department Manager, Viewer) | Only group_admin / company_admin / hr_manager / accountant carry permissions; `branch_manager` and `employee` roles are seeded **empty**; no branch- or department-scoped visibility anywhere; no field-level permissions ("edit salary: Limited") |
| **§14 REST admin API** (`/api/admin/employees…`) | Not implemented — web UI only; the only API is the sync endpoint pair |
| **§5.9 Assets tab** | No asset model/table at all |
| Bulk actions (checkbox multi-select on list) | Missing |
| Employee photo/avatar upload | Missing (list shows initials only) |
| **§6 normalized DB design** (`employee_salaries`, `_deductions`, `_payment_methods` tables) | Deliberately flattened onto `employees` — acceptable trade-off but diverges from PRD |

### Beyond the PRD (roadmap items from AGENT.md)
Performance module = mock view; Qiwa integration absent; Mudad file format unverified (self-flagged); end-of-service (EOS) calculation not found; no working-days/holiday calendar.

---

## 7. Prioritized recommendations

1. **Now (before any branch deployment):** S1 login throttling + rotate seeded passwords; S4 production `.env` hardening (debug off, DB password, HTTPS); S3 sync write allowlist.
2. **Short term:** B1 payroll metric fix (filter to current cycle); S2 per-branch sync tokens; B2/B3 leave balance + overlap + working-days checks; add FR-005 delete route (Super Admin only).
3. **Medium term:** Policies to replace ad-hoc checks; consolidate role system on spatie; implement branch/department-scoped roles per PRD §3; sync pull cursor (S7).
4. **Longer term:** normalize salary/deduction/payment tables (or formally document the flattened design as the canonical deviation); assets module; verify Mudad spec and Nitaqat counting rules; real performance module.
