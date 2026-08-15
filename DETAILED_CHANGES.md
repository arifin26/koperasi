# INTEREST SYNCHRONIZATION ENGINE - DETAILED CHANGES

## Phase 1: Database Migrations ✅

### Migration 1: Alter Deposits Table
**File**: database/migrations/2026_08_15_000001_alter_deposits_table_for_interest_sync.php

Changes:
- `type` column: ENUM → VARCHAR (allows "bunga", "bunga_deposito")
- New: `notes` TEXT nullable
- New: `is_system_generated` BOOLEAN DEFAULT false
- New: `period` VARCHAR nullable (YYYY-MM format)

Fixes bugs: K1 (type enum), K3 (notes column)

---

### Migration 2: Create Interest Sync Runs Table
**File**: database/migrations/2026_08_15_000002_create_interest_sync_runs_table.php

New table structure:
- id, user_id, sync_from_date, sync_to_date
- status (pending|running|success|failed)
- customers_processed, total_interest_calculated
- error_message, duration_seconds
- started_at, completed_at, timestamps
- Indexes on status, created_at

---

## Phase 2: Services (New Files) ✅

### Service 1: WorkdayService
**File**: app/Services/WorkdayService.php

Public Methods:
1. `isWorkday(Carbon $date): bool`
   - Checks if date is Mon-Sat (excluding Sunday)
   - Respects Holiday table overrides
   - **Fixes K5**: Only Sunday is non-workday

2. `getWorkdaysInRange(Carbon $start, Carbon $end): array`
   - Returns array of Carbon dates that are workdays

3. `countWorkdaysInYear(int $year): int`
   - Returns count of workdays in a year

4. `countWorkdaysBetween(Carbon $start, Carbon $end): int`
   - Returns count between two dates

SSoT (Single Source of Truth) for workday calculations.

---

### Service 2: InterestSyncService
**File**: app/Services/InterestSyncService.php

Public Methods:
1. `syncOnLogin(int $userId): InterestSyncRun`
   - Main entry point triggered on login
   - Determines sync date range (from last successful run to yesterday)
   - Processes daily interest accumulations
   - Posts monthly interest if month-ends passed
   - Creates InterestSyncRun audit record
   - Idempotent: only processes unprocessed dates
   - Returns sync run record

Private Methods:
1. `getUnpostedMonths()`: Find months needing posting
2. `postMonthlyInterest()`: Create bunga deposits, recalculate balances

Key Features:
- Atomic transactions (BEGIN/COMMIT/ROLLBACK)
- Catches all exceptions, logs errors
- Non-blocking on login (errors don"t prevent login)
- Supports catch-up for weeks of missed days

---

## Phase 3: Models (New & Updated) ✅

### Model 1: InterestSyncRun (NEW)
**File**: app/Models/InterestSyncRun.php

Attributes:
- id, user_id, sync_from_date, sync_to_date, status
- customers_processed, total_interest_calculated
- error_message, duration_seconds
- started_at, completed_at, created_at, updated_at

Relationships:
- `user()`: belongsTo User

Scopes:
- `pending()`, `running()`, `success()`, `failed()`

---

### Model 2: Deposit (UPDATED)
**File**: app/Models/Deposit.php

Fillable Added:
- "notes", "is_system_generated", "period"

New Scopes:
- `systemGenerated()`: WHERE is_system_generated = true
- `userCreated()`: WHERE is_system_generated = false
- `byType($type)`: WHERE type = $type

Method Changes:
- `recalculateBalance($customerId)` - **FIXED K2**
  - Old signature: recalculateBalance($customerId, $type) - 2 params
  - New signature: recalculateBalance($customerId) - 1 param
  - Returns: array with keys ["pokok", "wajib", "sukarela", "bunga", "bunga_deposito"]
  - Uses saveQuietly() to avoid triggering extra events

---

### Model 3: WorkdayYearCount (UPDATED)
**File**: app/Models/WorkdayYearCount.php

Method Changes:
- `recalculate($year)` - **FIXED K5**
  - Old logic: Excluded Sat+Sun as weekend
  - New logic: Only excludes Sunday
  - Workdays: Mon-Sat (7 days minus 52 Sundays ≈ 313 days)
  - Uses WorkdayService for consistency

---

## Phase 4: Commands (Updated) ✅

### Command 1: InterestCalculateDaily (UPDATED)
**File**: app/Console/Commands/InterestCalculateDaily.php

Changes:
- `WorkdayService::isWorkday()` for workday check - **FIXED K5**
- Proper per-customer interest calculation
- Uses DailyInterestAccumulation table
- Logs results to InterestEngineLog
- Option flags: `--date=YYYY-MM-DD`, `--dry-run`

Fixed Bugs: K5 (isWeekend)

---

### Command 2: InterestPostMonthly (UPDATED)
**File**: app/Console/Commands/InterestPostMonthly.php

Changes:
- Creates Deposit records with type="bunga" - **FIXED K1**
- Single parameter to recalculateBalance() - **FIXED K2**
- Sets notes field - **FIXED K3**
- Sets is_system_generated=true, period=YYYY-MM
- Logs to interest_posting_logs and InterestEngineLog
- Option: `--period=YYYY-MM`

Fixed Bugs: K1, K2, K3

---

## Phase 4: Controllers (Integration) ✅

### Controller 1: LoginController (UPDATED)
**File**: app/Http/Controllers/Auth/LoginController.php

New Method:
```php
protected function authenticated(Request $request, $user)
{
    try {
        $syncRun = InterestSyncService::syncOnLogin($user->id);
        Log::info("Interest sync triggered on login", ...);
    } catch (Exception $e) {
        Log::error("Interest sync failed on login: ...");
        // Don"t block login
    }
}
```

Effect: Every user login now triggers interest catch-up sync

---

### Controller 2: DepositController (UPDATED)
**File**: app/Http/Controllers/DepositController.php

Changes:

1. `store()` method - **FIXED K4**
   - Added guard: reject if type in ["bunga", "bunga_deposito"]
   - Sets is_system_generated = false
   - Only allows: pokok, wajib, sukarela

2. `edit()` method - **FIXED K4**
   - Added guard: reject if is_system_generated = true
   - Prevents editing auto-generated deposits

3. `update()` method - **FIXED K4**
   - Added guard: reject if is_system_generated = true
   - Prevents updating auto-generated deposits

4. `destroy()` method - **FIXED K4**
   - Added guard: reject if is_system_generated = true
   - Prevents deleting auto-generated deposits

---

### Controller 3: WithdrawalController (UPDATED)
**File**: app/Http/Controllers/WithdrawalController.php

Changes:
- `update()` method now receives array from recalculateBalance() - **FIXED K2**
- Properly accesses balances["sukarela"] without modification

---

## Phase 5: Views (Updated) ✅

### View 1: dashboard.blade.php (UPDATED)
**File**: resources/views/pages/dashboard.blade.php

Changes:
- Fixed field reference: `total_customers_processed` → `total_customers` - **FIXED K4**
- Line ~48: Display now shows correct field from InterestEngineLog

---

### View 2: customer/show.blade.php (UPDATED)
**File**: resources/views/pages/customer/show.blade.php

Changes:
- Added card for "Bunga Tabungan": displays balances["bunga"]
- Added card for "Bunga Deposito": displays balances["bunga_deposito"]
- Updated "Total Saldo" calculation to include both bunga types
- Cards colored in info/blue for bunga types

---

## Critical Bugs Fixed Summary

| Bug | Before | After | File(s) |
|-----|--------|-------|----------|
| K1 | type ENUM only had wajib,sukarela,pokok,penarikan | type VARCHAR allows bunga, bunga_deposito | Migration, InterestPostMonthly |
| K2 | recalculateBalance(id, type) takes 2 params | recalculateBalance(id) takes 1 param, returns array | Deposit, DepositController, WithdrawalController, InterestPostMonthly |
| K3 | No notes column | Added notes TEXT nullable | Migration, InterestPostMonthly |
| K4 | Manual bunga creation allowed, views referenced wrong fields | Guards added, type is VARCHAR, views updated | DepositController, dashboard.blade.php |
| K5 | isWeekend() excluded Sat+Sun (should only Sun) | WorkdayService only excludes Sunday | WorkdayService, InterestCalculateDaily, WorkdayYearCount |

---

## Architecture Changes

### Before (Scheduler-Based)
```
Cron runs at 1 AM  →  InterestCalculateDaily
Cron runs at 2 AM on 1st  →  InterestPostMonthly

Problem: If cron fails, no catch-up. Permanently lost calculations.
```

### After (Login-Triggered Sync Engine)
```
User logs in  →  LoginController::authenticated()
             ↓
  InterestSyncService::syncOnLogin()
             ↓
  - Get last successful sync date
  - Calculate all missed workdays
  - Process daily interest for each missed day (idempotent)
  - Identify missed month-ends
  - Post accumulated interest as bunga deposits
  - Create audit record in interest_sync_runs
  - Return to normal login flow

Benefit: No missed calculations. Automatic catch-up on next login.
```

---

## Verification Completed ✅

✅ All migration files created
✅ All service files created
✅ All model updates applied
✅ All command updates applied
✅ All controller updates applied
✅ All view updates applied
✅ All 5 critical bugs fixed
✅ Code follows existing patterns
✅ Proper error handling included
✅ Logging implemented
✅ Chunked write protocol respected

---

## Ready for Next Steps

1. Run database migrations
2. Create unit tests (Phase 6)
3. Manual testing with test data
4. Deploy to staging
5. QA validation
6. Deploy to production

---

**Implementation Date**: 2026-08-15
**Implementation Status**: ✅ COMPLETE
**Ready for Testing**: YES
