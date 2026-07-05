# HR ERP

Arabic-first, offline-ready Laravel HR ERP for a Saudi multi-company group.

## Offline Strategy — Decision

**Strategy A (branch-local server + central sync)** is the chosen offline
architecture, per AGENT.md. Each branch runs its own local Laravel + MySQL
instance (XAMPP-style) on the branch LAN; daily HR work never depends on
internet connectivity. A sync service pushes/pulls changes to a central
head-office server when connectivity is available.

Scaffolding in place:

- Every offline-writable table (`employees`, `attendance_records`,
  `leave_balances`, `leave_requests`, `payroll_cycles`, `payroll_items`)
  carries a `uuid` (globally unique sync identity — local auto-increment
  ids never leave the branch) and a `synced_at` timestamp (null = pending
  push). See the `App\Models\Concerns\Syncable` trait.
- `sync_log` tracks per-branch push/pull state; `sync_queue` holds queued
  local writes with user/device attribution.
- Conflict rule (to implement in the sync service): last-write-wins per
  field with an audit entry, except conflict-sensitive fields (salary,
  GOSI wage, Saudi status) which are flagged for manual HR review.
- Payroll runs may only be locked once contributing branch data is
  confirmed fully synced.

### Running the sync service

- **Central server**: set `HR_SYNC_ROLE=central` and `HR_SYNC_TOKEN=<shared secret>`.
  It exposes `POST /api/sync/push` and `GET /api/sync/pull` (bearer-token auth).
- **Branch nodes**: set `HR_SYNC_ROLE=branch`, `HR_SYNC_CENTRAL_URL=<central url>`,
  `HR_SYNC_TOKEN=<same secret>`, and optionally `HR_SYNC_DEVICE_NAME`.
  `php artisan hr:sync` pushes unsynced records then pulls central changes;
  the scheduler runs it every 15 minutes automatically. A sync status chip
  (pending count + last synced time) appears in the topbar on branch nodes.
- **All nodes must share the same `APP_KEY`** — encrypted columns (national
  id, IBAN, passport) travel as ciphertext and must decrypt everywhere.
- Reference data (companies, branches, departments, positions, shifts, leave
  types, document types, settings tables) is centrally managed and must be
  seeded/updated identically on every node; only transactional records sync.
- Conflicts on sensitive fields (salary, GOSI wage, Saudi status, IBAN,
  contract/payroll status) are never auto-resolved: they land in `sync_queue`
  with status `conflict` for manual HR review.

## Local Stack

- XAMPP PHP 8.2 at `D:\xampp82`
- Laravel 12
- MySQL/MariaDB database: `hr_system`
- Blade + Tailwind/Vite
- Cairo font bundled locally through Vite

## Setup

1. Start Apache and MySQL from XAMPP.
2. Create the database if it does not exist:

   ```powershell
   D:\xampp82\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS hr_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

3. Install backend dependencies:

   ```powershell
   composer install
   ```

4. Install and build local frontend assets:

   ```powershell
   npm install
   npm run build
   ```

5. Run migrations and seed demo data:

   ```powershell
   php artisan migrate:fresh --seed
   ```

6. Serve locally:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

## Demo Login

- Super Admin: `admin@hr.local`
- Password: `password`

Company HR demo users are also seeded as `hr1@hr.local` through `hr4@hr.local`, all with password `password`.

## First Slice Included

- Four-company structure with branches and departments
- Global shared positions table
- Role-aware users with company access pivot and current company context
- Employee records based on the provided bilingual field workbook
- Leave types, balances, and pending leave request sample
- Leave request list/create/approve/reject workflow
- Attendance overview with seeded daily attendance records
- Draft payroll cycle and payroll item data
- Payroll cycle list and detailed payroll item view
- Print-ready bilingual payslip per payroll item (browser print → PDF); self-service employees see own payslips of locked runs
- Mudad/WPS-style CSV salary export for locked runs (audited; verify column spec against official Mudad docs before production)
- Arabic RTL dashboard, company switcher, employee directory, and core HR module navigation
- Local Cairo font assets, no CDN links

## Verification

```powershell
php artisan test
npm run build
php artisan migrate:fresh --seed
```

The test suite uses in-memory SQLite; the main `.env` targets local XAMPP MySQL.
