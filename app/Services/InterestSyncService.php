<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DailyInterestAccumulation;
use App\Models\InterestRate;
use App\Models\InterestSyncRun;
use App\Models\WorkdayYearCount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterestSyncService
{
    /**
     * Triggered on user login to sync missed interest calculations and postings
     * - Calculates daily interest for missed workdays
     * - Posts monthly interest if month-end has passed
     * - Idempotent: only processes dates not yet processed
     *
     * @param int $userId
     * @return InterestSyncRun
     */
    public static function syncOnLogin(int $userId): InterestSyncRun
    {
        $startTime = microtime(true);
        $today = now();

        // Determine date range to sync
        // Get last successful sync run
        $lastRun = InterestSyncRun::where("status", "success")
            ->latest("sync_to_date")
            ->first();

        if ($lastRun) {
            $syncFromDate = $lastRun->sync_to_date->addDay();
        } else {
            // First time sync: go back 30 days or to first customer join date
            $firstCustomerJoin = Customer::where("status", "active")
                ->min("joined_at");
            $syncFromDate = $firstCustomerJoin 
                ? Carbon::parse($firstCustomerJoin)->startOfDay()
                : $today->copy()->subDays(30);
        }

        $syncToDate = $today->copy()->subDay(); // Sync up to yesterday (not today)

        // Create sync run record
        $syncRun = InterestSyncRun::create([
            "user_id" => $userId,
            "sync_from_date" => $syncFromDate,
            "sync_to_date" => $syncToDate,
            "status" => "running",
            "started_at" => now(),
        ]);

        try {
            DB::beginTransaction();

            $totalInterest = 0;
            $customersProcessed = 0;

            // Step 1: Process daily interest calculations for missed workdays
            $workdays = WorkdayService::getWorkdaysInRange($syncFromDate, $syncToDate);
            $workdayCount = WorkdayYearCount::where("year", $today->year)
                ->value("workday_count") ?? WorkdayYearCount::recalculate($today->year);

            foreach ($workdays as $workday) {
                $dateStr = $workday->format("Y-m-d");

                // Get rates
                $sukarelaRate = InterestRate::where("type", "tabungan_sukarela")
                    ->where("is_active", 1)
                    ->first();
                $wajibRate = InterestRate::where("type", "tabungan_wajib")
                    ->where("is_active", 1)
                    ->first();

                if (!$sukarelaRate && !$wajibRate) {
                    continue;
                }

                // Process per customer
                Customer::where("status", "active")
                    ->with("interestRate")
                    ->chunkById(100, function ($customers) use ($dateStr, $workdayCount, $sukarelaRate, $wajibRate, &$totalInterest, &$customersProcessed) {
                        $inserts = [];

                        foreach ($customers as $customer) {
                            // Check if already calculated for this date
                            $exists = DailyInterestAccumulation::where("customer_id", $customer->id)
                                ->where("calculation_date", $dateStr)
                                ->exists();

                            if ($exists) {
                                continue;
                            }

                            // Calculate balances
                            $balances = DB::table("deposits")
                                ->selectRaw("
                                    SUM(CASE WHEN type="sukarela" THEN amount ELSE 0 END) as sum_sukarela,
                                    SUM(CASE WHEN type="wajib" THEN amount ELSE 0 END) as sum_wajib,
                                    SUM(CASE WHEN type="penarikan" THEN amount ELSE 0 END) as sum_penarikan
                                ")
                                ->where("customer_id", $customer->id)
                                ->whereDate("created_at", "<", $dateStr)
                                ->first();

                            $saldoSukarela = ($balances->sum_sukarela ?? 0) - ($balances->sum_penarikan ?? 0);
                            $saldoWajib = $balances->sum_wajib ?? 0;

                            // Calculate interest
                            $customerSukarelaRate = $customer->interestRate ?? $sukarelaRate;

                            if ($saldoSukarela > 0 && $customerSukarelaRate) {
                                $bungaSukarela = floor(($saldoSukarela * ($customerSukarelaRate->rate_percent / 100)) / $workdayCount);
                                if ($bungaSukarela > 0) {
                                    $inserts[] = [
                                        "customer_id" => $customer->id,
                                        "savings_type" => "sukarela",
                                        "calculation_date" => $dateStr,
                                        "base_balance" => $saldoSukarela,
                                        "rate_percent" => $customerSukarelaRate->rate_percent,
                                        "interest_amount" => $bungaSukarela,
                                        "created_at" => now(),
                                        "updated_at" => now(),
                                    ];
                                    $totalInterest += $bungaSukarela;
                                }
                            }

                            if ($saldoWajib > 0 && $wajibRate) {
                                $bungaWajib = floor(($saldoWajib * ($wajibRate->rate_percent / 100)) / $workdayCount);
                                if ($bungaWajib > 0) {
                                    $inserts[] = [
                                        "customer_id" => $customer->id,
                                        "savings_type" => "wajib",
                                        "calculation_date" => $dateStr,
                                        "base_balance" => $saldoWajib,
                                        "rate_percent" => $wajibRate->rate_percent,
                                        "interest_amount" => $bungaWajib,
                                        "created_at" => now(),
                                        "updated_at" => now(),
                                    ];
                                    $totalInterest += $bungaWajib;
                                }
                            }

                            if ($saldoSukarela > 0 || $saldoWajib > 0) {
                                $customersProcessed++;
                            }
                        }

                        if (count($inserts) > 0) {
                            DailyInterestAccumulation::upsert(
                                $inserts,
                                ["customer_id", "calculation_date", "savings_type"],
                                ["base_balance", "rate_percent", "interest_amount", "updated_at"]
                            );
                        }
                    });
            }

            // Step 2: Check if any month-ends have passed and post accumulated interest
            $monthsToPost = self::getUnpostedMonths($syncFromDate, $syncToDate);
            foreach ($monthsToPost as $monthStr) {
                self::postMonthlyInterest($monthStr);
            }

            DB::commit();

            $duration = (int)(microtime(true) - $startTime);
            $syncRun->update([
                "status" => "success",
                "customers_processed" => $customersProcessed,
                "total_interest_calculated" => $totalInterest,
                "duration_seconds" => $duration,
                "completed_at" => now(),
            ]);

            Log::info("InterestSync completed", [
                "sync_run_id" => $syncRun->id,
                "from" => $syncFromDate,
                "to" => $syncToDate,
                "customers" => $customersProcessed,
                "interest" => $totalInterest,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("InterestSync failed: " . $e->getMessage());
            $syncRun->update([
                "status" => "failed",
                "error_message" => $e->getMessage(),
                "completed_at" => now(),
            ]);
        }

        return $syncRun;
    }

    /**
     * Get list of months that have month-ends within date range but not yet posted
     *
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @return array
     */
    private static function getUnpostedMonths(Carbon $fromDate, Carbon $toDate): array
    {
        $months = [];
        $current = $fromDate->copy()->startOfMonth();

        while ($current <= $toDate) {
            $monthEnd = $current->copy()->endOfMonth();

            if ($monthEnd <= $toDate) {
                $monthStr = $current->format("Y-m");

                // Check if this month already has successful posting
                $posted = DB::table("interest_posting_logs")
                    ->where("period", $monthStr)
                    ->where("status", "success")
                    ->exists();

                if (!$posted) {
                    $months[] = $monthStr;
                }
            }

            $current->addMonth();
        }

        return $months;
    }

    /**
     * Post accumulated interest for a month
     *
     * @param string $periodStr YYYY-MM
     * @return void
     */
    private static function postMonthlyInterest(string $periodStr): void
    {
        $startDate = Carbon::createFromFormat("Y-m", $periodStr)->startOfMonth()->format("Y-m-d");
        $endDate = Carbon::createFromFormat("Y-m", $periodStr)->endOfMonth()->format("Y-m-d");

        $accumulations = DailyInterestAccumulation::whereBetween("calculation_date", [$startDate, $endDate])
            ->where("is_posted", 0)
            ->selectRaw("customer_id, SUM(interest_amount) as total_interest")
            ->groupBy("customer_id")
            ->get();

        foreach ($accumulations as $acc) {
            // Create bunga deposit transaction
            Deposit::create([
                "customer_id" => $acc->customer_id,
                "type" => "bunga",
                "amount" => $acc->total_interest,
                "previous_balance" => 0,
                "current_balance" => 0,
                "notes" => "Bunga bulan " . $periodStr,
                "is_system_generated" => true,
                "period" => $periodStr,
                "created_at" => Carbon::createFromFormat("Y-m", $periodStr)->endOfMonth()->setTime(23, 59, 59),
            ]);

            Deposit::recalculateBalance($acc->customer_id);
        }

        // Mark as posted
        DailyInterestAccumulation::whereBetween("calculation_date", [$startDate, $endDate])
            ->where("is_posted", 0)
            ->update([
                "is_posted" => 1,
                "posted_at" => now(),
            ]);

        DB::table("interest_posting_logs")->updateOrInsert(
            ["period" => $periodStr],
            [
                "status" => "success",
                "total_customers" => $accumulations->count(),
                "total_interest" => $accumulations->sum("total_interest"),
                "notes" => "Posted via InterestSync on login",
            ]
        );
    }
}
