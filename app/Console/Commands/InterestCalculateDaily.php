<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\InterestRate;
use App\Models\Holiday;
use App\Models\WorkdayYearCount;
use App\Models\DailyInterestAccumulation;
use App\Models\InterestEngineLog;
use App\Services\WorkdayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterestCalculateDaily extends Command
{
    protected $signature = "interest:calculate-daily {--date= : The date to calculate for (YYYY-MM-DD), defaults to yesterday} {--dry-run : Simulate without saving}";
    protected $description = "Menghitung akumulasi bunga harian untuk semua nasabah";

    public function handle()
    {
        $startTime = microtime(true);
        $dateStr = $this->option("date") ?? date("Y-m-d", strtotime("yesterday"));
        $date = Carbon::parse($dateStr);
        $dryRun = $this->option("dry-run");

        $this->info("Starting daily interest calculation for $dateStr");

        // 1. Check if this is a workday using WorkdayService (K5 fix)
        if (!WorkdayService::isWorkday($date)) {
            $reason = "Bukan Hari Kerja (Minggu atau Libur)";
            $this->info("Skipping: $reason");
            if (!$dryRun) {
                InterestEngineLog::updateOrCreate(
                    ["run_date" => $dateStr],
                    [
                        "status" => "skipped",
                        "reason" => $reason,
                    ]
                );
            }
            return 0;
        }

        // 2. Get interest rates
        $sukarelaRate = InterestRate::where("type", "tabungan_sukarela")->where("is_active", 1)->first();
        $wajibRate = InterestRate::where("type", "tabungan_wajib")->where("is_active", 1)->first();

        if (!$sukarelaRate && !$wajibRate) {
            $this->error("No active interest rate found for tabungan.");
            if (!$dryRun) {
                InterestEngineLog::updateOrCreate(
                    ["run_date" => $dateStr],
                    [
                        "status" => "failed",
                        "reason" => "No active interest rates found",
                        "error_message" => "Both tabungan_sukarela and tabungan_wajib have no active rate.",
                    ]
                );
            }
            return 1;
        }

        // 3. Get workday count for this year
        $year = $date->year;
        $workdayCount = WorkdayYearCount::where("year", $year)->value("workday_count") ?? WorkdayYearCount::recalculate($year);

        $totalCustomersProcessed = 0;
        $totalInterestCalculated = 0;

        // 4. Process per customer
        Customer::where("status", "active")->with("interestRate")->chunkById(100, function ($customers) use ($date, $dateStr, $workdayCount, $sukarelaRate, $wajibRate, &$totalCustomersProcessed, &$totalInterestCalculated, $dryRun) {
            $inserts = [];

            foreach ($customers as $customer) {
                // Calculate real balance up to yesterday
                $balances = DB::table("deposits")
                    ->selectRaw("
                        SUM(CASE WHEN type='sukarela' THEN amount ELSE 0 END) as sum_sukarela,
                        SUM(CASE WHEN type='wajib' THEN amount ELSE 0 END) as sum_wajib,
                        SUM(CASE WHEN type='penarikan' THEN amount ELSE 0 END) as sum_penarikan
                    ")
                    ->where("customer_id", $customer->id)
                    ->whereDate("created_at", "<", $dateStr)
                    ->first();

                $saldoSukarela = ($balances->sum_sukarela ?? 0) - ($balances->sum_penarikan ?? 0);
                $saldoWajib = $balances->sum_wajib ?? 0;

                // Determine rate for this customer (personal or fallback to global)
                $customerSukarelaRate = $customer->interestRate ?? $sukarelaRate;

                // Calculate sukarela interest
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
                        $totalInterestCalculated += $bungaSukarela;
                    }
                }

                // Calculate wajib interest
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
                        $totalInterestCalculated += $bungaWajib;
                    }
                }

                if ($saldoSukarela > 0 || $saldoWajib > 0) {
                    $totalCustomersProcessed++;
                }
            }

            if (!$dryRun && count($inserts) > 0) {
                DailyInterestAccumulation::upsert(
                    $inserts,
                    ["customer_id", "calculation_date", "savings_type"],
                    ["base_balance", "rate_percent", "interest_amount", "updated_at"]
                );
            }
        });

        $duration = (int)(microtime(true) - $startTime);

        if (!$dryRun) {
            InterestEngineLog::updateOrCreate(
                ["run_date" => $dateStr],
                [
                    "status" => "success",
                    "reason" => "Processed normally",
                    "total_customers" => $totalCustomersProcessed,
                    "total_interest" => $totalInterestCalculated,
                    "duration_seconds" => $duration,
                ]
            );
        }

        $this->info("Completed. $totalCustomersProcessed customers processed. Total Interest: Rp $totalInterestCalculated");
        return 0;
    }
}
