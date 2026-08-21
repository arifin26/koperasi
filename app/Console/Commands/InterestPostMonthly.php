<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyInterestAccumulation;
use App\Models\InterestPostingLog;
use App\Models\Deposit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterestPostMonthly extends Command
{
    protected $signature = 'interest:post-monthly {--period= : The period to post (YYYY-MM), defaults to last month} {--force : Force posting even if already done} {--dry-run : Simulate without saving}';
    protected $description = 'Posting akumulasi bunga bulanan ke rekening tabungan nasabah';

    public function handle()
    {
        $periodStr = $this->option('period') ?? date('Y-m', strtotime('first day of last month'));
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Starting monthly interest posting for period: $periodStr");

        if (!$force) {
            $existingLog = InterestPostingLog::where('period', $periodStr)->where('status', 'success')->first();
            if ($existingLog) {
                $this->info("Period $periodStr has already been successfully posted. Skipping.");
                return 0;
            }
        }

        // Find all unposted accumulations for this period
        $startDate = Carbon::createFromFormat('Y-m', $periodStr)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromFormat('Y-m', $periodStr)->endOfMonth()->format('Y-m-d');

        $accumulations = DailyInterestAccumulation::whereBetween('calculation_date', [$startDate, $endDate])
            ->where('is_posted', 0)
            ->selectRaw('customer_id, SUM(interest_amount) as total_interest')
            ->groupBy('customer_id')
            ->get();

        if ($accumulations->isEmpty()) {
            $this->info("No unposted interest found for period $periodStr.");
            return 0;
        }

        $totalCustomers = $accumulations->count();
        $totalInterest = $accumulations->sum('total_interest');
        $this->info("Found $totalCustomers customers with total interest Rp $totalInterest");

        if ($dryRun) {
            $this->info("Dry run complete. No data was saved.");
            return 0;
        }

        DB::beginTransaction();
        try {
            // Posting loop
            foreach ($accumulations as $acc) {
                // Insert a 'bunga' type transaction
                Deposit::create([
                    'customer_id' => $acc->customer_id,
                    'type' => 'bunga',
                    'amount' => $acc->total_interest,
                    'previous_balance' => 0, // Ignored, will be recalculated
                    'current_balance' => 0,  // Ignored, will be recalculated
                    'notes' => 'Bunga bulan ' . $periodStr,
                    'created_at' => Carbon::createFromFormat('Y-m', $periodStr)->endOfMonth()->setTime(23,59,59),
                    'updated_at' => now(),
                ]);

                // Recalculate balance for the customer (using the model's helper)
                Deposit::recalculateBalance($acc->customer_id);
            }

            // Mark as posted
            DailyInterestAccumulation::whereBetween('calculation_date', [$startDate, $endDate])
                ->where('is_posted', 0)
                ->update([
                    'is_posted' => 1,
                    'posted_at' => now()
                ]);

            // Log
            InterestPostingLog::updateOrCreate(
                ['period' => $periodStr],
                [
                    'status' => 'success',
                    'total_customers' => $totalCustomers,
                    'total_interest' => $totalInterest,
                    'notes' => $force ? 'Forced run' : 'Auto run'
                ]
            );

            DB::commit();
            $this->info("Posting completed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("InterestPostMonthly Failed: " . $e->getMessage());
            InterestPostingLog::updateOrCreate(
                ['period' => $periodStr],
                [
                    'status' => 'failed',
                    'notes' => $e->getMessage()
                ]
            );
            $this->error("Failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
