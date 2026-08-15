<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\DailyInterestAccumulation;
use App\Models\Deposit;
use App\Models\InterestEngineLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterestPostMonthly extends Command
{
    protected $signature = "interest:post-monthly {--period= : Period to post (YYYY-MM), defaults to last month}";
    protected $description = "Post accumulated daily interest sebagai transaksi bunga setiap akhir bulan";

    public function handle()
    {
        $startTime = microtime(true);
        $period = $this->option("period") ?? Carbon::now()->subMonth()->format("Y-m");

        $this->info("Starting monthly interest posting for period: $period");

        // Parse period
        try {
            $startDate = Carbon::createFromFormat("Y-m", $period)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } catch (\Exception $e) {
            $this->error("Invalid period format. Use YYYY-MM (e.g., 2026-08)");
            return 1;
        }

        $startDateStr = $startDate->format("Y-m-d");
        $endDateStr = $endDate->format("Y-m-d");

        // Check if already posted
        $alreadyPosted = DB::table("interest_posting_logs")
            ->where("period", $period)
            ->where("status", "success")
            ->exists();

        if ($alreadyPosted) {
            $this->warn("Period $period already posted.");
            return 0;
        }

        DB::beginTransaction();

        try {
            // Get accumulated interest per customer for this period
            $accumulations = DailyInterestAccumulation::whereBetween("calculation_date", [$startDateStr, $endDateStr])
                ->where("is_posted", 0)
                ->selectRaw("customer_id, SUM(interest_amount) as total_interest")
                ->groupBy("customer_id")
                ->get();

            $totalCustomers = 0;
            $totalInterest = 0;

            foreach ($accumulations as $acc) {
                // Create bunga deposit (K1 fix - type is now "bunga", not enum)
                Deposit::create([
                    "customer_id" => $acc->customer_id,
                    "type" => "bunga",
                    "amount" => $acc->total_interest,
                    "previous_balance" => 0,
                    "current_balance" => 0,
                    "notes" => "Bunga tabungan bulan $period",
                    "is_system_generated" => true,
                    "period" => $period,
                    "created_at" => $endDate->setTime(23, 59, 59),
                ]);

                // Recalculate balance for this customer (K2 fix - single parameter)
                Deposit::recalculateBalance($acc->customer_id);

                $totalCustomers++;
                $totalInterest += $acc->total_interest;
            }

            // Mark daily accumulations as posted
            DailyInterestAccumulation::whereBetween("calculation_date", [$startDateStr, $endDateStr])
                ->where("is_posted", 0)
                ->update([
                    "is_posted" => 1,
                    "posted_at" => now(),
                ]);

            // Log the posting
            DB::table("interest_posting_logs")->updateOrInsert(
                ["period" => $period],
                [
                    "status" => "success",
                    "total_customers" => $totalCustomers,
                    "total_interest" => $totalInterest,
                    "notes" => "Posted via command",
                    "created_at" => now(),
                    "updated_at" => now(),
                ]
            );

            DB::commit();

            $duration = (int)(microtime(true) - $startTime);
            InterestEngineLog::create([
                "run_date" => $period,
                "status" => "success",
                "reason" => "Monthly posting completed",
                "total_customers" => $totalCustomers,
                "total_interest" => $totalInterest,
                "duration_seconds" => $duration,
            ]);

            $this->info("Completed. $totalCustomers customers posted. Total Interest: Rp $totalInterest");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("InterestPostMonthly failed: " . $e->getMessage());
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
