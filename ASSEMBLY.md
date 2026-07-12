# ASSEMBLY.md — Shared Files, Ownership Rules & Environment Standards

This document exists because several files in this project are jointly owned
or touched by both developers, and several environment settings caused real,
time-consuming bugs during stabilization. Read this before editing any file
listed below.

---

## Environment standard (hard rule)

- **Host: always use `http://127.0.0.1:8000`.** Never `localhost:8000` in the
  browser. Mixing hosts breaks session cookies silently (the browser treats
  `localhost` and `127.0.0.1` as different origins), causing an infinite
  login->dashboard->login redirect loop with no visible error.
- `.env` must have:APP_URL=http://127.0.0.1:8000
  SESSION_DOMAIN=null
  SANCTUM_STATEFUL_DOMAINS=localhost:9000,127.0.0.1:8000
  CACHE_STORE=file`CACHE_STORE=file` is intentional: the project's `cache` table does not
  match Laravel's expected cache-table schema (id/created_at/updated_at
  instead of key/value/expiration). Do not switch to `CACHE_STORE=database`
  until that table is fixed or replaced.
- Both `php artisan serve` and `npm run dev` must be running simultaneously.
  Every Blade layout uses `@vite(...)`; without the Vite dev server, pages
  fail with a manifest error or render unstyled.

---

## Shared / co-owned files — coordinate before editing

### `bootstrap/app.php`

Registers middleware aliases and loads BOTH developers' route files:

- `routes/api.php` + `routes/landlord_api.php`
- `routes/web.php` + `routes/landlord_web.php`

Never replace this file without preserving both `require` pairs. If a role's
pages 404 or its API routes return "route not defined," check this file
first — it is the single most common point of silent breakage in this
project's history.

### `app/Http/Controllers/DashboardController.php`

ONE file, THREE methods, TWO owners:

- `landlord()` — Developer A
- `caretaker()`, `tenant()` — Developer B

**Never overwrite this file with a version containing only one developer's
method(s).** This has happened multiple times during stabilization and each
time broke a dashboard with "Call to undefined method." Always view the
current file (`grep -n "public function" app/Http/Controllers/DashboardController.php`)
before editing, and always keep all three methods present.

### `routes/api.php` (Dev B) + `routes/landlord_api.php` (Dev A)

Split by design to avoid merge conflicts — each developer edits only their
own file. Two cross-ownership exceptions, by deliberate design, not error:

- Landlord's maintenance approve/reject routes live in `routes/api.php`
  (Dev B's file) because they call Dev B's `MaintenanceController`.
- Landlord's payments-list route lives in `routes/landlord_api.php` but
  calls `PaymentController::landlordIndex` (Dev B's controller).

### `routes/web.php` (Dev B) + `routes/landlord_web.php` (Dev A)

Same split as above. Every named route referenced by `route()` in any layout
must exist in one of these two files, with the exact name the layout expects
(watch for `.index` suffix mismatches — this caused repeated 500 errors
during stabilization).

### `app/Http/Controllers/PaymentController.php` (Dev B, one cross-owned method)

Owns: `tenantHistory`, `submitTransactionCode`, `verify` (Dev B).
Also owns: `landlordIndex` (Dev A's landlord payments page calls this).
All four methods must always be present together.

### `app/Http/Controllers/AuthController.php` (Dev B)

Five methods, always together: `login`, `logout`, `me`, `forgotPassword`,
`resetPassword`. The last two were added after initial generation — if this
file is ever restored from an old copy, they will be missing.

### Models co-owned across the split

`Tenant`, `Caretaker`, `TenantOccupancy` are referenced by both developers'
controllers. There is exactly ONE copy of each in `app/Models/`. Do not let
either developer maintain a separate version — check `git status` / diff
before assuming a "local copy" is safe to overwrite the shared one.

### `resources/views/layouts/{landlord,caretaker,tenant}.blade.php`

Each is a BASE SHELL — it does NOT `@extends` anything. Rules that must hold
for all three, always:

- **`<!DOCTYPE html>` must be the literal first line of the file.** A Blade
  comment (`{{-- ... --}}`) placed before it pushes the browser into Quirks
  Mode, which silently disables PWA manifest detection with no console error
  beyond an easy-to-miss "Quirks Mode" notice. This exact bug cost significant
  debugging time — check `head -1` on all three layout files after any edit.
- All three must include the PWA manifest link, theme-color meta, and
  apple-touch-icon link in `<head>`.
- All three must load `@vite(['resources/js/app.js'])` and `@stack('scripts')`.
- Logout is a real `<form method="POST">` with `@csrf`, never a plain `<a>`.

### `resources/js/app.js`

Must contain `import './pwa-register';` — without this single line, the
service worker never registers, even though `pwa-register.js` and `sw.js`
both exist and are individually correct. Confirmed to fail silently (no
console error) if missing.

### `public/sw.js`

Single owner: Developer A (PWA layer). Do not let Developer B edit this file
without coordinating — a service worker install failure (e.g. from one
missing cached URL) blocks the ENTIRE install via `cache.addAll()`'s
all-or-nothing behavior. Current implementation uses `Promise.allSettled()`
per-URL specifically to avoid this. `replayQueue()` distinguishes permanent
failures (4xx — dropped from queue) from temporary ones (network error, 5xx
— kept queued for retry). Do not revert this distinction.

### `public/offline.html`

Referenced by `sw.js`'s SHELL array and its fetch fallback. Must exist at
this exact path or service worker install silently fails with
`TypeError: Failed to execute 'addAll' on 'Cache'` (though current version
tolerates a missing shell URL gracefully via `allSettled`).

---

## Known, deliberate gaps (not bugs — do not "fix" without discussion)

- **Admin role** has no dedicated dashboard; logs in and lands on the
  caretaker dashboard as a stopgap. A real admin panel is out of scope for
  this stabilization pass.
- **Notifications UI** — backend fully functional (model, controller,
  events/listeners writing rows), but no layout has a bell icon or dropdown
  wired to `/api/notifications` or `/api/notifications/unread-count`.
  `resources/js/notifications.js` (if present) is currently unused.
- **`cache` table** has the wrong schema for Laravel's database cache driver
  (see environment section above). `CACHE_STORE=file` works around this;
  the table itself is not fixed.
- **Maintenance duplicate guard**: a tenant cannot submit a second
  maintenance request for the same unit while one is open and less than 7
  days old. This is intentional business logic (`MaintenanceController::store`),
  not a bug — it will produce a 422 during testing if you reuse a tenant/unit
  that already has an open request.

---

## Database-level safeguards added during hardening

`tenant_occupancies` has two generated columns (`current_tenant_guard`,
`current_unit_guard`) with unique indexes, enforcing at the database level
that a tenant or unit can have at most one row with `is_current = 1`. This
exists ALONGSIDE the application-level check in `AllocateTenantRequest`
(which still runs first and gives a clean 422) — the DB constraint is a
safety net against race conditions under concurrent requests, not the
primary validation path. If you see a raw
`SQLSTATE[23000]: Integrity constraint violation` instead of a clean 422,
the app-level check was bypassed somehow — investigate the calling code path.

---

## If a page 500s with "Route [x] not defined" or "Call to undefined method"

This project has repeatedly experienced files being silently reverted to an
earlier/emptier version during merges. Before writing a fix, always:

1. `grep -n "public function" <file>` (for controllers) or
   `head -5 <file>` (for views/routes) to see the CURRENT actual state.
2. Compare against what this document says should be there.
3. Restore the missing piece — do not assume the file is fine because it
   existed at some point.
