# AGENT.md — Saudi HR Management System (Build Instructions)

## Role

You are acting as a senior HR digital transformation consultant, Saudi
labor compliance specialist, and SaaS product strategist — now in
**build mode**. Your job is to design and implement a web-based HR
management application for companies in the Saudi Arabia market,
following the goal, scope, and constraints in this document.

Read this entire file before writing any code. When a decision isn't
covered here, state your assumption explicitly and proceed — don't block
on it unless it affects compliance-sensitive logic (see Guiding
Principles).

\---

## Original Goal

Build a web-based HR management application for companies in the Saudi
Arabia market. The application must be designed specifically for Saudi
companies and support: bilingual Arabic/English usage, multi-branch
companies, employee records, payroll preparation, attendance, leave
management, document expiry tracking, contracts, GOSI-related salary
fields, Saudi/non-Saudi classification, and compliance-oriented HR
reporting. The system must also **work reliably with no internet
connection** (offline-first), and is being built for **four specific
group companies**, not as a generic multi-tenant SaaS product.

### Deployment Context — Group Companies

This is a **group HR system** for four legal entities operating as one
group. Model them as four `companies` records under a shared group
structure from day one — don't build single-company-first and retrofit:

1. **Amniat أمنيات **
2. **Amniat Factory مصنع أمنيات**
3. **PTC تقنيات الدهان للتجارة**
4. **PTC Construction تقنيات الدهان للمقاولات**

Implications:

* Each company has its own CR number, GOSI establishment number, Qiwa
entity, and Nitaqat/Saudization position — these must **never** be
aggregated silently. A "group Nitaqat report" is a rollup view, not a
merge of the underlying data.
* Employees, contracts, payroll runs, and documents belong to exactly
one company (and one branch within it). Cross-company employee
transfers are a deliberate workflow (new contract + termination of
the old one), not a simple field edit — GOSI and Qiwa treat this as a
real employment change.
* A **Group Admin** role (see [User Roles](#user-roles--permissions))
can view/report across all four companies; every other role is scoped
to the company (and branch) they belong to by default.
* Seed these four companies as real reference data in the seeder (not
just "Company A/B" placeholders), so every module is developed and
tested against the actual structure from the start.

### Language

**Arabic is the primary/default language; English is secondary.** This
affects more than string files — see the dedicated
[Language \& Localization](#language--localization-arabic-first)
section below.

### Connectivity

**The system must function fully with no internet connection** —
covering data entry, viewing records, and daily HR operations at branch
level, with sync to a central server when connectivity returns. See the
dedicated [Offline-First Architecture](#offline-first-architecture)
section below; this is a first-class requirement, not a stretch goal.

Deliverables (produce and maintain these as living artifacts as you
build, not just in a one-time doc):

1. Main business goal of the application
2. Target users and customer segments (this group's four companies)
3. Main problems the app solves
4. Saudi-market-specific requirements
5. Core MVP modules
6. Database structure
7. User roles and permissions
8. Employee profile fields required for Saudi companies
9. Payroll fields and salary calculation logic
10. Leave, attendance, deductions, and overtime workflows
11. Document and expiry alert system
12. Dashboard and reporting requirements
13. Compliance-related features for Qiwa, Mudad, GOSI, Saudization/Nitaqat readiness
14. Security requirements
15. Future integration roadmap
16. Technology stack
17. UI/UX direction for a professional enterprise HR system (Arabic-first)
18. Phased development roadmap from MVP to full ERP module
19. Offline-first architecture and sync strategy

\---

## Guiding Principles (non-negotiable)

1. **Compliance figures are configuration, never hard-coded.** Nitaqat
percentages, GOSI contribution rates, minimum wage thresholds, and
any other government-set number live in a settings/reference table
editable without a code deploy. Regulators change these periodically;
the code must not need to change when they do.
2. **Bilingual is structural, not cosmetic, and Arabic is the default.**
Arabic/English must extend to database content (names, labels),
generated documents/PDFs, reports, and notifications — not just
static UI strings. The application boots in Arabic (RTL) by default
for every new user/session; English is an explicit, remembered
opt-in per user, not the other way around. Build and QA screens in
Arabic first — don't design in English and translate afterward.
3. **Offline is a first-class requirement, not a fallback.** Branch-level
HR staff must be able to record attendance, view employee data, and
perform daily operations with zero connectivity, with changes syncing
automatically once online. Don't bolt this on after the web app is
built — it shapes the architecture (local data layer, sync/conflict
strategy, queued writes) from Phase 1. See
[Offline-First Architecture](#offline-first-architecture).
4. **Multi-branch and multi-entity by default.** Every employee, payroll
run, and report is scoped to a branch and a legal entity (one of the
four group companies). Don't design as single-branch-first-then-retrofit.
5. **Audit everything sensitive.** Salary, GOSI wage, nationality/
Saudi status, termination, and document changes are logged with
who/when/old-value/new-value from day one.
6. **Don't fabricate regulatory detail.** Where you don't have verified
current GOSI rates, Nitaqat weights, or similar figures, use clearly
marked placeholder values with a comment instructing the operator to
verify against Qiwa/GOSI/HRSD official sources before production use.
Never present an assumed number as authoritative.
7. **Design for external integration, don't fake it.** Qiwa, Mudad, and
GOSI integrations may be stubbed at MVP stage, but the data model and
service boundaries must already fit a future real API connection
(dedicated service classes, no business logic entangled with a mock).

\---

## Recommended Toolset \& Tech Stack

Use this stack unless the existing project already dictates otherwise
(check for an existing `composer.json` / `package.json` first and adapt
rather than override).

**Backend**

* PHP 8.2+ / Laravel 11.x — primary framework
* MySQL 8+ — primary datastore (utf8mb4 for Arabic content)
* Redis — cache, queues, rate limiting
* Laravel queues — for expiry-alert jobs, notifications, report generation
* `spatie/laravel-permission` — roles \& permissions
* `spatie/laravel-activitylog` — audit trail on sensitive models
* `maatwebsite/excel` — bulk import/export (employees, payroll, Nitaqat data)
* `barryvdh/laravel-dompdf` or `spatie/laravel-pdf` — payslips, contracts, reports
* Laravel localization (`lang/ar`, `lang/en`) — bilingual strings
* Laravel Sanctum — API auth for future mobile/portal clients

**Frontend — choice depends on the offline strategy (see below)**

* If using the **branch-local-server strategy** (recommended, matches
the existing XAMPP/Laravel setup): Livewire + Alpine.js is fine, since
"offline" is solved at the network/deployment layer, not in the browser.
* If using the **browser-PWA strategy**: Livewire is a poor fit (it
needs a live server round-trip per interaction). Use Vue 3 + Inertia.js
or a plain Vue/React SPA consuming a Laravel API instead, so the
frontend can run against a local IndexedDB store when offline.
* Either way: Tailwind CSS with RTL as the **default** direction (not an
override) — see [Language \& Localization](#language--localization-arabic-first).
* Chart.js or ApexCharts — dashboards (must render correctly offline;
avoid chart libraries that fetch external assets/fonts at runtime).

**Infrastructure**

* Local dev: Laravel Sail or XAMPP (match what the team already uses)
* Storage: local disk at each branch/company location for MVP,
centralized S3-compatible (or Saudi-hosted equivalent) storage for the
synced central copy, once compliance/data-residency requirements are
confirmed
* CI: GitHub Actions — migrations, tests, static analysis on every PR

**Why this stack**: Laravel's ecosystem (permissions, activity log,
Excel, PDF, localization, queues) covers nearly every requirement below
out of the box, which matters for an MVP timeline. Don't introduce a
second backend framework or a NoSQL store without a specific reason tied
to a requirement in this document. The offline requirement is the one
exception that may force a frontend decision away from pure Livewire —
resolve this explicitly in Phase 1, don't discover it mid-build.

\---

## Offline-First Architecture

Offline capability is required, not optional. Decide the strategy
explicitly in Phase 1 — this is an architectural fork, not a checkbox
added later.

> **DECIDED 2026-07-02: Strategy A** (branch-local server + central
> sync) is chosen and implemented — see the
> [Decision Log](#decision-log-living) and README for the concrete
> node-role configuration, sync contract, and conflict rules.

### Choose one strategy (state the choice in the repo's README once decided)

**Strategy A — Branch-local server + sync (recommended default)**
Each company/branch runs its own local Laravel + MySQL instance (on a
local machine or small server on the branch LAN, using the same
XAMPP-style setup already in use). Staff at that branch use the system
fully over the local network with zero dependency on internet
connectivity for day-to-day work: attendance, leave requests, employee
lookups, document uploads all read/write to the local database.
A **sync service** (a Laravel command run on a schedule, or triggered on
reconnect) pushes new/changed records to a central head-office server
and pulls down changes relevant to that branch, when internet is
available.

* Simplest to build correctly given the current XAMPP/Laravel stack.
* Payroll consolidation, group Nitaqat reporting, and cross-branch
dashboards run against the central server once synced.
* Requires: a `sync\_log` table (per branch) tracking what's been pushed/
pulled and when; a conflict resolution rule (default: last-write-wins
per field with an audit entry, unless a field is marked
conflict-sensitive — e.g. salary — in which case conflicting changes
are flagged for manual HR review instead of auto-resolved).

**Strategy B — Browser-based PWA with local storage**
A single central server, but the browser app works offline via a
service worker and a local IndexedDB store (e.g. via Dexie.js),
queuing writes locally and syncing to the server when connectivity
returns. Fits better if branches don't have reliable local
infrastructure and staff instead use laptops/tablets that go online
intermittently.

* Requires moving off Livewire for any screen that must work offline
(see Tech Stack note above).
* More engineering complexity in conflict resolution and queued-write
UI feedback ("saved locally, will sync") than Strategy A.

**Default recommendation for this project**: Strategy A, given the
existing XAMPP/Laravel setup and that this is a fixed set of four
company locations (not a distributed SaaS customer base) — confirm this
against the actual branch network setup before building; if branches
genuinely have no local server and only intermittent laptop/tablet
connectivity, switch to Strategy B instead of forcing Strategy A.

### Non-negotiable requirements regardless of strategy

* Every write made offline must be attributable (user, timestamp,
device) and auditable once synced — don't lose the "who did this
offline" trail.
* Read operations (looking up an employee, viewing a payslip, checking
a leave balance) must work offline without exception — HR staff
cannot be blocked from viewing existing data because of connectivity.
* Payroll runs should be **locked/finalized only when the run's data
is confirmed fully synced** across all contributing branches, to
avoid finalizing payroll on stale or partial data.
* Document uploads made offline queue locally and upload once online;
don't block the HR workflow on the upload completing immediately.
* Build a visible **sync status indicator** in the UI (last synced
time, pending changes count) so HR staff always know if they're
looking at fully current data or a local/offline snapshot.

\---

## Language \& Localization (Arabic-first)

* **Arabic is the default and primary language of the system.** Every
new session, new user, and every generated document (contracts,
payslips, reports) defaults to Arabic unless a user has explicitly
set their own preference to English.
* Build and review every screen in Arabic first. Treat English as a
secondary, fully-supported translation — not the reference version
that Arabic is derived from. This affects wording, layout, and
information hierarchy decisions, not just which `lang/` file is
loaded by default.
* RTL is the default layout direction. Components, tables, form flows,
and PDF templates should be authored RTL-first, then verified in LTR
for English, not the other way around.
* Store bilingual content as first-class column pairs where content is
user-authored or company-specific (e.g. `name\_ar` / `name\_en` on
companies, branches, departments, job titles) rather than relying on
translation files for anything that isn't static UI chrome.
* Dates: use the Gregorian calendar for all system records and payroll
(matches GOSI/Qiwa/Mudad conventions) but display Hijri alongside
Gregorian where Saudi HR users would expect it (e.g. leave calendars),
clearly labeled — never Hijri-only where compliance dates are
involved, to avoid ambiguity.
* Numbers: use Western Arabic numerals (0-9) by default even in Arabic
UI, unless a specific document type requires Eastern Arabic numerals
— confirm with the actual users rather than assuming.

\---

## Module Build Order (MVP → full system)

Build in this order — each module should be functional (migrations,
models, basic CRUD UI) before starting the next, since later modules
depend on earlier data structures.

### Phase 1 — Foundation

1. **Confirm offline strategy (A or B)** against actual branch network
infrastructure — see [Offline-First Architecture](#offline-first-architecture).
This decision affects the frontend framework choice and must be
settled before other Phase 1 work locks in.
2. **Group / company / branch structure** — `companies` seeded with the
four real entities (Amniat, Amniat Factory, PTC, PTC Construction),
`branches` under each, legal entity fields (CR number, GOSI
establishment number, Qiwa entity ID, Unified National Number).
3. **Auth, roles \& permissions** — see [User Roles](#user-roles--permissions),
including the Group Admin role scoped across all four companies.
4. **Localization scaffolding, Arabic-first** — `ar` as default locale,
`en` as opt-in, RTL layout as default, bilingual model fields pattern
(e.g. `name\_ar`, `name\_en`). See [Language \& Localization](#language--localization-arabic-first).
5. **Offline data layer scaffolding** — per the chosen strategy: either
the branch-local DB + `sync\_log` table (Strategy A) or the
IndexedDB/service-worker scaffolding (Strategy B). Build this before
the employee module so every subsequent module inherits offline
support instead of needing retrofitting.
6. **Employee core profile** — see [Employee Profile Fields](#employee-profile-fields).

### Phase 2 — Core HR

7. **Contracts \& document management** — contract records, document
uploads, expiry dates.
8. **Document expiry alert engine** — see dedicated section below.
Alerts must be queued and delivered correctly even if generated
while a branch is offline (queue locally, send once synced/online).
9. **Attendance** — check-in/out records, work schedule per branch.
Must work fully offline at the branch (Strategy A) or queue locally
(Strategy B).
10. **Leave management** — leave types, balances, request/approval chain.

### Phase 3 — Payroll \& Compliance

11. **Payroll preparation** — salary components, GOSI wage calculation,
deductions, overtime — see dedicated section. Payroll runs draw on
synced, confirmed data across all four companies' branches — see
offline requirements on payroll locking above.
12. **Saudization/Nitaqat readiness module** — reuse/extend the
configurable calculator pattern (weighted Saudi headcount vs.
activity target, stored per-activity and adjustable without code
change), computed **per company** (Amniat, Amniat Factory, PTC, PTC
Construction each have their own Nitaqat position) with a group
rollup view for the Group Admin.
13. **Compliance dashboard** — Qiwa/Mudad/GOSI readiness indicators
(can be manually-updated status flags at MVP, API-driven later),
per company and grouped.

### Phase 4 — Reporting \& Polish

14. **Dashboards** (executive/group, HR, branch manager views), Arabic
by default.
15. **Reporting engine** — exportable, bilingual, scheduled reports,
per company and consolidated group reports.
16. **Notifications** — email/SMS/in-app for approvals, expiries, payroll
runs, resilient to offline queuing.

### Phase 5 — Integration \& Scale (post-MVP)

17. Qiwa API integration (real contract/employee sync, per company entity).
18. Mudad integration (payroll file submission, per company entity).
19. GOSI integration (contribution sync/validation, per company entity).
20. Employee self-service portal / mobile app (with its own offline
considerations for staff without reliable connectivity).
21. Full ERP expansion (finance, procurement, asset management links)
— particularly relevant for **PTC Construction** (project/site-based
cost tracking) and **Amniat Factory** (production-linked HR costs),
worth flagging as likely earliest ERP-expansion candidates.

\---

## Database Structure — Key Entities

Design around these core tables (exact columns per later sections);
don't collapse Saudi-specific fields into generic "custom fields" JSON —
they need to be first-class, queryable columns for reporting and
compliance logic.

```
groups (the holding structure over the four companies — even if there's
        only ever one group row, model it, don't assume a single-group
        constant)
companies (belongs to groups — seeded: Amniat, Amniat Factory, PTC, PTC Construction)
branches (belongs to companies)
employees (belongs to branches)
employee\_documents (iqama, passport, contract, certifications — polymorphic or dedicated)
contracts (belongs to employees)
economic\_activities (Nitaqat reference data, per company — see prior calculator module)
nitaqat\_settings (weights/thresholds, configurable, per company or shared)
nitaqat\_calculation\_batches (historical calculation snapshots, per company)
leave\_types
leave\_balances (per employee, per leave\_type, per year)
leave\_requests (approval chain state)
attendance\_records
payroll\_runs (per branch/company, per period)
payroll\_items (per employee, per payroll\_run — basic, housing, GOSI wage, deductions, overtime)
gosi\_settings (contribution rates, thresholds — configurable, per company since
               establishment numbers differ)
roles / permissions (via spatie/laravel-permission)
activity\_log (via spatie/laravel-activitylog)
notifications
sync\_log (Strategy A only — per branch: last\_synced\_at, pending\_push\_count,
          pending\_pull\_count, last\_conflict\_at)
sync\_queue (Strategy A/B — queued local writes awaiting sync, with device/
            user attribution, created\_at, synced\_at)
```

Use `SoftDeletes` on `employees`, `contracts`, and `payroll\_runs` —
HR/payroll records must never be hard-deleted. Every table that can be
written to offline needs a `synced\_at` (nullable) column and a stable
UUID identity so records created independently on different branch
databases don't collide when synced to the central server.
*(Amended 2026-07-02: implemented as a unique `uuid` column alongside
local auto-increment PKs, with FK→uuid translation on the sync wire —
see the Decision Log — rather than uuid primary keys.)*

\---

## User Roles \& Permissions

Minimum role set for MVP (extend via `spatie/laravel-permission`, don't
hard-code role checks in controllers):

* **Group Admin** — visibility and reporting across all four companies
(Amniat, Amniat Factory, PTC, PTC Construction); this is the top
operational role for this system (no separate SaaS-vendor "Super
Admin" is needed since this is a fixed group deployment, not a
multi-tenant product for external customers).
* **Company Admin** — full access within one company, all its branches
* **HR Manager** — full HR module access, restricted from company-level
billing/settings
* **Branch Manager** — employee and leave/attendance visibility scoped
to their branch only, no salary visibility by default (configurable)
* **Payroll Officer** — payroll module access, restricted elsewhere
* **Employee (self-service)** — own profile, own leave requests, own
payslips only

Enforce scoping (branch/company) at the query level (global scopes or
policy classes), not just at the UI/menu level.

\---

## Employee Profile Fields

Required fields for Saudi-market compliance and payroll, beyond generic
HR fields (name, contact, job title, department, hire date):

* National ID / Iqama number + expiry date
* Nationality, and derived **Saudi / non-Saudi classification** flag
* Passport number + expiry date (non-Saudi)
* GOSI registration number
* GOSI-eligible wage (may differ from gross salary — see payroll section)
* Marital status (affects some GOSI/benefit calculations)
* Bank name + IBAN (for Mudad/payroll file generation)
* Contract type (full-time/part-time, limited/unlimited term)
* Branch and department assignment
* Qualification level, job title per Saudi occupational classification
(relevant to Nitaqat weighted calculation and sector-specific
Saudization decisions)
* Disability status (relevant to Nitaqat weighting)
* Sponsorship/visa status fields (non-Saudi)
* Emergency contact
* Document set: iqama/passport copy, contract copy, qualification
certificates, medical insurance card

\---

## Payroll Fields \& Salary Calculation Logic

Core salary components per employee per payroll run:

* Basic salary
* Housing allowance
* Transportation allowance
* Other allowances (configurable list)
* **GOSI-eligible wage** = basic + housing (confirm current GOSI
definition against official source before hardcoding the formula —
this has changed historically; keep it as a configurable formula/rule,
not a fixed constant)
* GOSI employee contribution (Saudi: pension + unemployment/SANED;
non-Saudi: occupational hazards only — rates must come from
`gosi\_settings`, not hardcoded)
* GOSI employer contribution (for cost reporting, not employee deduction)
* Overtime pay (rate multiplier configurable, default reference: 1.5x
hourly rate — verify against Saudi Labor Law before shipping as default)
* Deductions: unpaid leave days, penalties, loan repayments
* Net salary = gross − deductions − employee GOSI contribution

Payroll run workflow: draft → review → approve → lock → export (bank
file / Mudad format). Locked payroll runs are immutable; corrections go
through a new adjustment run, never an edit to a locked one.

\---

## Leave, Attendance, Deductions \& Overtime Workflows

* **Leave**: request → manager approval → HR approval (configurable
chain length per company) → balance deduction → calendar reflection.
Support annual leave, sick leave, unpaid leave, and Saudi-specific
leave types (e.g. Hajj leave, marriage leave, bereavement leave,
maternity/paternity per current labor law — verify durations against
official source, don't assume).
* **Attendance**: check-in/out, late/absence flagging, integration point
for biometric devices left open (interface, not hard dependency) for
Phase 5.
* **Deductions**: unpaid leave and penalty deductions must be traceable
back to the originating leave request or disciplinary record, not
entered as free-text payroll adjustments.
* **Overtime**: computed from attendance records against scheduled
hours, subject to a configurable cap and multiplier.

\---

## Document \& Expiry Alert System

* Track expiry dates for: iqama, passport, contract, work permit,
professional licenses/certifications, medical insurance.
* Configurable alert lead time per document type (e.g. 90/60/30 days
before iqama expiry).
* Alerts route to HR Manager and (optionally) the employee, via
in-app notification + email, generated by a scheduled queue job (not
computed on page load).
* Maintain an expiry dashboard view listing all documents expiring in
the next N days across branches, filterable by branch/document type.

\---

## Dashboard \& Reporting Requirements

* **Executive dashboard**: headcount, Saudization percentage vs. target
(per activity), payroll cost trend, turnover.
* **HR dashboard**: pending leave approvals, expiring documents, open
onboarding/offboarding tasks.
* **Branch manager dashboard**: scoped to their branch only.
* **Reports** (bilingual, exportable to Excel/PDF): headcount by
nationality, GOSI contribution summary, payroll register, leave
balance report, Nitaqat position report, document expiry report.
* All reports must respect role-based data scoping — a branch manager
exporting a report gets branch-scoped data, not company-wide.

\---

## Compliance Features — Qiwa / Mudad / GOSI / Nitaqat

* **Nitaqat/Saudization**: build on the configurable weighted-calculator
pattern — economic activity reference table with per-activity target
percentages (updatable without code change), employee weighting
(full/part-time, salary threshold, disability, etc.), and a Nitaqat
position report per branch/company. Do not hardcode band thresholds
or percentages as fixed constants; keep them in settings tables with a
`verified\_at` field so operators know how current the data is.
* **GOSI**: contribution rates and eligible-wage rules in `gosi\_settings`,
not in code. Support both Saudi and non-Saudi contribution schemes.
* **Qiwa**: MVP stage — maintain contract and employee data in a shape
ready to sync (matching Qiwa's data fields conceptually); actual API
integration is Phase 5.
* **Mudad**: MVP stage — payroll export in a Mudad-compatible file
format/structure; actual submission API integration is Phase 5.
* Provide a single **Compliance Status** dashboard aggregating: Nitaqat
band, documents expiring soon, GOSI registration gaps, contract
documentation completeness — so an HR manager can see compliance
health at a glance.

\---

## Security Requirements

* Encrypt at rest: national ID/iqama numbers, bank account/IBAN,
passport numbers, salary figures (field-level encryption via
Laravel's `encrypted` cast where feasible).
* TLS in transit everywhere; no exceptions for internal-only traffic.
* Role-based access control enforced at the query/policy layer, tested
with more than one role during development, not assumed from
permission definitions alone.
* Full audit trail (via `spatie/laravel-activitylog`) on: salary changes,
nationality/Saudi-status changes, termination, document uploads/
deletions, role/permission changes.
* Session security: enforce timeout, 2FA available for Company Admin
and Payroll Officer roles at minimum.
* File upload validation (type, size, virus scan hook) for document
uploads.
* Data residency: confirm hosting requirements against Saudi data
protection regulation (PDPL) before choosing a storage region — flag
this as a decision point, don't assume offshore hosting is acceptable.

\---

## Future Integration Roadmap

1. Qiwa API — live contract and employee data sync
2. Mudad API — direct payroll submission
3. GOSI API — contribution validation/sync
4. Biometric attendance device integration
5. Employee self-service mobile app
6. Bank file direct integration (beyond static export)
7. Expansion toward full ERP: finance/GL posting from payroll, asset
management, procurement — as separate modules sharing the same
company/branch/employee core

\---

## UI/UX Direction

* Clean, professional, enterprise tone — not consumer-app playful.
* **Arabic/RTL is the default experience**, designed first; language
switch to English must re-flow layout correctly (LTR), not just
mirror text — treat this as the secondary, verified-after-the-fact
view, not the primary design target.
* Persistent, unobtrusive **sync status indicator** (see Offline-First
Architecture) so users always know if they're on fully current data.
* Data-dense tables with strong filtering/sorting for HR/payroll users
who work in bulk (not a chat-style or card-heavy consumer layout).
* Status uses consistent color coding across the app (e.g. compliance
bands, document expiry urgency) — define the palette once, reuse
everywhere, don't invent new color meanings per screen.
* Every destructive or payroll-locking action requires explicit
confirmation with a summary of impact.

\---

## Phased Development Roadmap

* **Phase 1 (Weeks 1–4)**: Foundation — company/branch, auth/roles,
localization, employee core profile.
* **Phase 2 (Weeks 5–9)**: Core HR — contracts, documents, expiry
alerts, attendance, leave.
* **Phase 3 (Weeks 10–15)**: Payroll \& compliance — payroll engine,
GOSI fields, Nitaqat module, compliance dashboard.
* **Phase 4 (Weeks 16–18)**: Reporting, dashboards, notifications,
UI polish.
* **Phase 5 (Ongoing, post-MVP)**: Qiwa/Mudad/GOSI live integrations,
self-service portal, ERP expansion.

Re-baseline this roadmap once Phase 1 is complete — real velocity will
differ from the estimate above.

\---

## Working Rules for the Agent

* Before implementing any compliance-sensitive number (GOSI rate,
Nitaqat weight, leave-duration entitlement, overtime multiplier),
search for or ask for the current official figure rather than
assuming a remembered value; mark it clearly as unverified if you
can't confirm it, and store it as configuration either way.
* Prefer extending existing project structure and conventions over
introducing a parallel pattern — check for existing models/migrations
before creating new ones.
* Build iteratively per the module order above; don't start payroll
logic before employee and branch structures exist.
* Keep bilingual fields and RTL support in mind from the first
migration, not retrofitted later.
* **Keep this file current.** When a new rule, convention, or
architectural decision is made during the build and it does not break
the architecture above, record it in the
[Decision Log](#decision-log-living). Amend the affected section
inline only when the original text materially conflicts with what was
actually built — otherwise the log alone is the record.

\---

## Decision Log (living)

Architecture-consistent decisions made during the build, newest batch
first. Follow these patterns when extending the system; don't reinvent
them per module.

### 2026-07-04 — PRD Employee Module (Bayzat reference) adoption + GitHub backup

Outcome of reviewing `PRD_Employee_Module_HRMS_Bayzat_Reference_EN.docx`
against this architecture — what was adopted, adapted, or rejected:

* **REJECTED — 1:1 satellite tables** (`employee_personal_infos`,
`employee_job_infos`, `employee_salaries`, …). The flat `employees`
table stays: splitting would break sync identity, encryption/hash
columns, and audit wiring for zero functional gain. The PRD's groupings
live as profile tabs, not tables.
* **ADOPTED — the bilingual field workbook is mirrored 1:1 on
`employees`**, including monthly figure columns (overtime, deductions,
GOSI items, cash/bank-transfer splits, `remaining_salary`) and legacy
`employment_status` / `branch_text` / `bank` / `job_title` free-text
columns. These are the spreadsheet-compatible master-data entry surface;
`payroll_items` remains the authoritative per-cycle payroll record.
Don't remove these columns to "normalize" — imports depend on them.
* **Unified 7-value employee status** (`active, inactive, probation,
on_leave, suspended, resigned, terminated` — `Employee::STATUSES`) with
Arabic labels in `STATUS_LABELS_AR`. Status changes go through
`Employee::changeStatus()` (reason + actor recorded in
`employee_status_histories`, syncable) via `POST /employees/{id}/status`
— never a bare field edit. Deactivating states require a reason.
The legacy `employment_status` column coexists for spreadsheet
compatibility but the unified `status` drives all logic.
* **`Employee::EMPLOYED_STATUSES`** (`active, probation, on_leave,
suspended`) defines who counts for Nitaqat/headcount via
`scopeEmployed()` — PLACEHOLDER judgment, verify against official
GOSI/Nitaqat counting rules.
* **New profile fields:** `marital_status`, `address`,
`emergency_contact_name/phone`, `manager_id` (direct manager, same
company, not self), `work_location`, `probation_end_date`,
`avatar_path`. Contract vocabulary is `fixed / indefinite / training /
temporary` (`Employee::CONTRACT_TYPES`); `open` was renamed
`indefinite` everywhere.
* **Weighted profile completion (PRD §8):** sections 20/20/20/15/15/10
(basic/personal/work/contract/salary/required-documents), computed in
`Employee::profileCompletion()` and **stored** in
`employees.profile_completion` for SQL filtering/stat cards. Recomputed
on employee save; contracts/documents changes trigger
`recomputeProfileCompletionQuietly()` (quiet update — derived data must
not re-dirty sync state or the audit log). Threshold bands via
`Employee::completionBand()` (<50 / <75 / <90 / ≥90).
* **Validation additions:** minimum age 15 (birth_date), manager must
be same-company and not self, `bank_name` required with IBAN,
probation end after contract start.
* **Directory data-quality cards** (probation / expired iqama /
incomplete <75%) and branch/department/status/contract-type filters on
the employee list.
* **Deferred from the PRD:** Excel import/export (maatwebsite/excel not
yet installed), assets module, admin REST API (§14 — Sanctum is
Phase 5), hr_officer/viewer/department_manager roles.
* **Backup:** the canonical off-machine backup is the GitHub remote
`https://github.com/gawhara/hr-system` (`origin`, branch `main`). Keep
it current after significant milestones.

### 2026-07-04 — Payslips + Mudad salary file export

* **Payslips are print-ready HTML, not server-generated PDF.** Route
`GET /payroll/{payroll}/items/{item}/payslip` renders a standalone A4
RTL bilingual (ar primary / en secondary) payslip view
(`payroll/payslip.blade.php`); PDF is produced via browser print. This
was chosen deliberately: dompdf's Arabic shaping is unreliable, and a
dependency-free HTML view works on offline branch nodes. If server-side
PDFs are later needed (e.g. email attachments), add `mpdf/mpdf` (good
Arabic support) — not dompdf.
* **Payslip access:** payroll staff (`view-payroll`, scoped to current
company); employee self-service sees **own** payslips of **locked runs
only** — draft/under-review figures are never shown to employees.
Unlocked runs render with a "غير نهائي — PRELIMINARY" watermark for staff.
* **Mudad export** (`GET /payroll/{payroll}/export/mudad`): WPS-style CSV
(UTF-8 BOM), `manage-payroll` only, **locked runs only**. Every export is
written to the activity log (`event: mudad_export`) since it contains
IBANs and salaries. The column layout is a PLACEHOLDER shape — verify
against the official Mudad file specification before production upload.

### 2026-07-02 — UI branding, navigation, and employee company gateway

* **Branding:** visible app branding is now **SMARS HR**. `APP_NAME`
is set to `SMARS HR`; the login page and copyright text use the same
name. Do not reintroduce `Horizon Enterprise` or `HR ERP` in visible UI.
* **Visual direction:** the active UI theme is a polished purple-gradient
enterprise style with Arabic-first RTL layout. Sidebar, login,
dashboard, cards, buttons, form controls, and Nitaqat calculator should
continue using the shared purple surface/gradient language in
`resources/css/app.css`.
* **Top navbar restored:** the authenticated layout has the top navbar
again, including search, Nitaqat calculator shortcut, notifications,
language switch, and user badge. The earlier sidebar company selector
remains removed.
* **Recruitment removed:** the Recruitment / Talent acquisition module
is intentionally removed from navigation and routing. `/recruitment`
should stay unavailable unless the business explicitly asks to rebuild
that module.
* **Dashboard layout:** the main dashboard is focused on the four group
companies as company dashboard cards, with no extra explanatory blocks
outside the cards.
* **Employees entry flow:** `/employees` is now a company-selection
gateway only. It shows the four company cards in a centered 2x2 grid;
clicking a card opens the filtered employee directory via
`/employees?company_id={id}`. The employee table, filters, and profile
completion ring appear only after a company has been selected.
* **Network dev access:** current LAN dev server is commonly run as
`php artisan serve --host=0.0.0.0 --port=8011`, reachable on the local
network at `http://192.168.10.92:8011/` while that machine keeps the
same IPv4 address.

### 2026-07-02 — Foundation hardening + Phase 2/3 build

* **Offline strategy: A (branch-local server + central sync) — DECIDED
and implemented.** Node role via `HR_SYNC_ROLE` env
(`standalone` | `branch` | `central`). Push/pull over token-authenticated
HTTP (`POST /api/sync/push`, `GET /api/sync/pull`); the `hr:sync`
command runs every 15 minutes on branch nodes via the scheduler.
* **Sync identity is a `uuid` column, not uuid primary keys.** Tables
keep local auto-increment PKs; every offline-writable table carries
`uuid` (unique) + `synced_at` via the `App\Models\Concerns\Syncable`
trait. FKs between synced records are translated to `<relation>_uuid`
on the wire (`SyncRegistry`); local ids never leave a node. This amends
the original "UUID primary/foreign key" instruction — same
no-collision guarantee, far less churn.
* **Conflict rule:** record-level last-write-wins by `updated_at`,
EXCEPT conflict-sensitive fields (salary components, GOSI wage, Saudi
status, IBAN, national id, contract/payroll status). Those quarantine
the incoming record in `sync_queue` with status `conflict` for manual
HR review — never auto-resolved (`SyncApplier`).
* **Reference data does not sync.** Companies, branches, departments,
positions, shifts, leave types, document types, and all settings tables
are centrally managed and must be seeded/updated identically on every
node. Only transactional records travel.
* **All nodes share one `APP_KEY`** — encrypted columns travel as
ciphertext and must decrypt everywhere.
* **Encryption boundary:** `national_id`, `iban`, `passport_id` use
Laravel's `encrypted` cast; `national_id_hash` (sha256, unique) provides
uniqueness and exact-match search. Salary columns stay plaintext
deliberately — payroll math, dashboards, and Nitaqat thresholds
aggregate in SQL; rely on database/tablespace encryption for those.
Audit-log entries mask encrypted PII (first 2 + last 2 chars).
* **Roles are spatie-canonical:** `group_admin`, `company_admin`,
`hr_manager`, `branch_manager`, `payroll_officer`, `employee`. The
legacy `users.role` string is a write-side shorthand that auto-assigns
the matching spatie role on user creation. Module gates use granular
permissions (`view-payroll`, `view-documents`, `view-reports`,
`view-nitaqat`, `manage-employees`, …) — never direct role string
comparisons in controllers.
* **Payroll workflow:** `draft → under_review → approved → locked`,
with one backward edge (review rejection → draft). Transition map lives
on `PayrollCycle::TRANSITIONS`; each step stamps who/when. Locked runs
are immutable at the model layer (`PayrollItem` throws on
update/delete); corrections go through adjustment runs
(`parent_cycle_id` + `run_sequence`, so one regular run per period stays
DB-enforced). Branch nodes cannot lock a run whose data is unsynced.
* **GOSI math lives only in `GosiCalculatorService`,** reading
`gosi_settings`: per-company rows override a group-wide default row
(`company_id = null`); `verified_at = null` marks unverified placeholder
figures that must be confirmed against official sources.
* **Expiry alerts:** lead times per document type in
`document_types.alert_days` (JSON, e.g. `[90, 60, 30]`). The daily
`hr:send-expiry-alerts` job sends one alert for the smallest crossed
threshold and records all crossed thresholds as consumed
(`document_expiry_alerts`) — offline-gap safe, never re-fires.
In-app (database) notifications always; mail is a per-deployment opt-in
(`HR_EXPIRY_ALERT_MAIL`) so branch servers without SMTP never fail.
* **Locale:** `users.locale` (`ar` default; `en` is a remembered
per-user opt-in via `POST /locale`). Author all layout with Tailwind
logical utilities (`start-*`, `end-*`, `ms-*`, `ps-*`, `pe-*`) — never
physical `left/right` — so `dir` switching re-flows instead of breaking.
Static chrome strings live in `resources/lang/{ar,en}/`; business data
stays bilingual column pairs.
* **Employee profile is tabbed** (overview / work & contracts /
documents / leave / attendance / payslips / activity). Self-service
users see their own payslips and attendance rows but never the audit
timeline or links into multi-employee payroll views. The directory
list shows a profile-completion ring computed from 10 real compliance
fields (`Employee::getProfileCompletionPercentAttribute`).

\---

http://127.0.0.1:8000/dashboard

D:\\xampp82\\htdocs\\hr-system

D:\\xampp82
