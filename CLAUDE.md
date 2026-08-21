# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Koperasi Swamitra** — a Laravel 9 / PHP 8 web app for managing an Indonesian savings-and-loan cooperative (*koperasi simpan pinjam*): members, savings, withdrawals, time deposits, interest accrual, collector visits, and collateral foreclosure.

The UI, route URIs, validation messages, and flash messages are all in **Indonesian**. Class names, methods, and DB columns are in English. Keep this split when adding code — e.g. `Route::resource('/nasabah', CustomerController::class, ['names' => 'customer'])`.

## Commands

```bash
# Full stack (MySQL 8.1 + php-fpm + nginx + queue worker) — the documented way to run this
docker compose up -d          # app on http://localhost, first boot runs migrate:fresh --seed

# Local dev without Docker
php artisan serve
npm run watch                 # Laravel Mix (webpack) — compiles resources/js + resources/sass
npm run prod

# Tests (PHPUnit 9; only the stock Laravel example tests exist so far)
php artisan test
php artisan test --filter=ExampleTest
vendor/bin/phpunit tests/Feature/ExampleTest.php

php artisan migrate:fresh --seed   # reseeds the three demo logins below
```

Seeded logins (all password `admin`): `manajer` / `teller` / `kolektor`.

There is no `.env.example`; `.env.docker` is the reference for required env vars. A schema dump lives at `docs/swamitra.sql` and an ERD at `docs/ERD.png`.

### Interest engine (the core scheduled logic)

```bash
php artisan interest:calculate-daily [--date=YYYY-MM-DD] [--dry-run]   # default: yesterday
php artisan interest:post-monthly    [--period=YYYY-MM] [--force] [--dry-run]  # default: last month
php artisan deposit:pay-interest     [--date=YYYY-MM-DD] [--dry-run]
```

All three are registered in `app/Console/Kernel.php` on `Asia/Jakarta` (00:05 daily, 00:30 on the 1st, 01:00 daily) and need `php artisan schedule:run` driven by cron/Task Scheduler. Every command supports `--dry-run` — use it when verifying changes against real data. `ENGINE_TUTORIAL_DAN_DOKUMENTASI_LIBUR.md` is the operator-facing doc for these.

## Architecture

### Balances are derived, never incremented

`Deposit` is a single append-only transaction ledger for all savings movement, discriminated by `type`: `pokok` (principal), `wajib` (mandatory), `sukarela` (voluntary/daily), `penarikan` (withdrawal, subtracts from `sukarela`), and `bunga` (posted interest — note this value is **not** in the `deposits.type` enum from the original migration). `WithdrawalController` writes `penarikan` rows into the same table; `DepositController` filters them out with `whereNot('type', 'penarikan')`.

`previous_balance` / `current_balance` on each row are **caches**. Controllers write them as `0` on insert, then call `Deposit::recalculateBalance($customerId)`, which replays the customer's entire transaction history in `created_at, id` order and rewrites both columns via `saveQuietly()`. Any code that creates, edits, deletes, or restores a `Deposit` must call `recalculateBalance` afterward, inside the surrounding transaction. Because ordering is by `created_at`, backdated entries (the create forms allow one) correctly reshuffle the running balance.

Note: `InterestPostMonthly` calls `Deposit::recalculateBalance($customerId, $date)` with a second argument the method signature does not accept — the extra arg is silently ignored, so the recalc always spans full history.

### Interest engine data flow

```
Holiday (CRUD)
  └─HolidayObserver→ WorkdayYearCount::recalculate($year)
                       = weekdays − holidays-on-weekdays + workdays-on-weekends

interest:calculate-daily   skips weekends unless a Holiday row of type 'workday' overrides;
                           skips dates with a Holiday row of type 'holiday'
  reads deposits (raw SQL SUM up to the date) + InterestRate
  writes DailyInterestAccumulation  (upsert on customer_id+calculation_date+savings_type → idempotent)
  writes InterestEngineLog          (one row per run_date: success | skipped | failed)

interest:post-monthly
  sums unposted accumulations per customer → creates one `bunga` Deposit dated month-end 23:59:59
  flips is_posted, writes InterestPostingLog (one row per period; re-run is a no-op without --force)

deposit:pay-interest
  FixedDeposits whose start_date day-of-month == today → floor(amount * rate/100 / 12)
  creates a `bunga` Deposit + a DepositInterestPayment row (idempotent on fixed_deposit_id+period)
```

Rate resolution for daily accrual: a customer's own `interest_rate_id` wins, falling back to the globally active `tabungan_sukarela` rate. Rounding is **always `floor()`** to whole rupiah — preserve that. `interest_rates.type` was widened to `VARCHAR(50)` by a later migration, so newer type strings (`simpanan`) coexist with legacy ones (`tabungan_sukarela`); `DepositController::create` queries both.

### Controller conventions

Controllers extend `App\Http\Controllers\Controller`, which provides shared state and helpers each subclass configures in its constructor:

```php
$this->title = 'Transaksi - Simpanan';   // breadcrumb/page title, passed into every view
$this->code  = 'SI';                     // prefix for buildTransactionCode() → "SI-00042"
```

plus `buildTitle($section)`, `storeImage`/`updateImage`/`deleteImage` (writes to `storage/app/public`, expects a `photo` file field).

Index actions are dual-purpose: `if ($request->ajax())` returns a **Yajra DataTables** JSON response (action-button HTML built inline in `addColumn('action', ...)` and whitelisted via `rawColumns`), otherwise they return the Blade view that boots the DataTable. Filters arrive as query params on the AJAX call.

Printing uses **barryvdh/laravel-dompdf**: `Pdf::loadView(...)` against `pages/*/print.blade.php` (extends `layouts.pdf`, list reports) or `receipt.blade.php` / `destroy-receipt.blade.php` (extends `layouts.receipt`, per-transaction slips). Report views fetch `User::where('role', 'manager')->first()` for the signature block. Amounts are spelled out in words by `App\Helpers\TerbilangHelper::make($nominal)`.

Deletion is soft everywhere (`SoftDeletes` on `User`, `Customer`, `Deposit`, `FixedDeposit`, `Holiday`). `TrashController` (manager-only) exposes restore / force-delete over a `$module` string switch — adding a soft-deleted model means adding a case to all three switches there. Receipt routes for deleted records use plain `{id}` rather than route-model binding, precisely because the record is trashed.

### Authorization

There is no policy layer. `App\Http\Middleware\CheckRole` is registered as the `role` alias and is applied **inside controller constructors**, not on routes:

```php
$this->middleware('role:manager')->except(['index']);
```

Roles are a plain `users.role` string: `manager`, `teller`, `collector`, `viewer`. Views also branch on `auth()->user()->role` directly. Registration is disabled (`Auth::routes(['register' => false])`).

The `/api/*` routes in `routes/web.php` (customer search, balance lookup, holiday check, active rates) are **session-authenticated AJAX endpoints**, not the Sanctum API — `routes/api.php` is effectively unused.

### Frontend

AdminLTE 3 + Bootstrap 5, served from `public/plugins` and `public/css/adminlte.min.css` as static vendor assets — Mix only builds `resources/js/app.js` and `resources/sass/app.scss`. Views live under `resources/views/pages/<module>/` with `layouts/app.blade.php` as the shell.

## Gotchas

- **CRLF/BOM**: this repo has been developed on Windows and deployed to Linux; `FIX_BOM_ON_SERVER.sh` and `FIX_QUOTES_ON_SERVER.sh` exist because UTF-8 BOMs and smart-quote mangling have broken PHP files on the server before. Write plain ASCII quotes and no BOM.
- Models use `protected $guarded = []` (or `['id']`) — every column is mass-assignable, so validation in `app/Http/Requests/*` is the only input gate.
- `Schema::defaultStringLength(191)` is set in `AppServiceProvider`.
- The `.md` files at the repo root (`fsd_koperasi.md`, `implementation_plan.md`, `feature_analysis.md`, `task_progress.md`) are planning/spec documents of varying staleness, not generated docs.
