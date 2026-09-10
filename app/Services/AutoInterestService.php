<?php

namespace App\Services;

use App\Models\AutoInterestRunLog;
use Illuminate\Support\Facades\Log;

class AutoInterestService
{
    protected SavingsInterestService $savingsService;
    protected DepositInterestService $depositService;

    public function __construct(
        SavingsInterestService $savingsService,
        DepositInterestService $depositService
    ) {
        $this->savingsService = $savingsService;
        $this->depositService = $depositService;
    }

    /**
     * Run auto-posting for the given period if needed
     *
     * @param string $period Period in Y-m format (e.g., '2026-09')
     * @param bool $force Force run even if already processed
     * @return array Result containing status and details
     */
    public function runIfNeeded(string $period, bool $force = false): array
    {
        try {
            // Check if already successfully processed
            if (!$force && AutoInterestRunLog::isProcessedFor($period)) {
                Log::info("Auto interest run skipped for period {$period}: already processed");
                return ['already_done' => true, 'period' => $period];
            }

            // Check if there's a stale pending run (older than 5 minutes)
            if (!$force && AutoInterestRunLog::hasStalePendingFor($period)) {
                Log::warning("Auto interest run retrying for period {$period}: previous run stale");
                // Continue with retry
            }

            // Create or update pending log
            $log = AutoInterestRunLog::getOrCreatePending($period);

            // Calculate date range for this period
            // If posting on tanggal 1, we want to post bunga for bulan kemarin (last month)
            $periodDate = \Carbon\Carbon::createFromFormat('Y-m', $period);
            $endOfPeriod = $periodDate->endOfMonth()->format('Y-m-d');
            $today = now()->format('Y-m-d');

            Log::info("Starting auto interest posting for period {$period}", [
                'end_of_period' => $endOfPeriod,
                'today' => $today,
            ]);

            // Process savings interest
            $savingsResult = $this->processSavingsInterest($endOfPeriod);
            Log::info("Savings interest processed", ['result' => $savingsResult]);

            // Process deposit interest
            $depositResult = $this->processDepositInterest($today);
            Log::info("Deposit interest processed", ['result' => $depositResult]);

            // Mark as success
            $log->markSuccess($savingsResult, $depositResult);

            Log::info("Auto interest posting completed successfully for period {$period}");

            return [
                'success' => true,
                'period' => $period,
                'savings' => $savingsResult,
                'deposit' => $depositResult,
                'timestamp' => $log->triggered_at,
            ];
        } catch (\Exception $e) {
            Log::error("Auto interest posting failed for period {$period}: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark as failed
            if (isset($log)) {
                $log->markFailed($e->getMessage());
            } else {
                // If log creation itself failed, try to create failed log
                try {
                    $failLog = AutoInterestRunLog::getOrCreatePending($period);
                    $failLog->markFailed($e->getMessage());
                } catch (\Exception $logE) {
                    Log::critical("Failed to log auto interest error", ['original_error' => $e->getMessage()]);
                }
            }

            return [
                'success' => false,
                'period' => $period,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process savings interest posting for a date
     */
    protected function processSavingsInterest(string $processDateStr): array
    {
        try {
            $result = $this->savingsService->processSavingsInterest($processDateStr);
            return [
                'processed' => true,
                'date' => $processDateStr,
                'details' => $result,
            ];
        } catch (\Exception $e) {
            throw new \Exception("Savings interest processing failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Process fixed deposit interest posting for a date
     */
    protected function processDepositInterest(string $processDateStr): array
    {
        try {
            $result = $this->depositService->processDepositInterest($processDateStr);
            return [
                'processed' => true,
                'date' => $processDateStr,
                'details' => $result,
            ];
        } catch (\Exception $e) {
            throw new \Exception("Deposit interest processing failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Check for missed periods and run catch-up
     * Called to handle cases where tanggal 1 was missed (e.g., long holidays)
     */
    public function runCatchUp(): array
    {
        $results = [];
        $now = now();

        // Check up to 3 months back for unprocessed periods
        for ($i = 0; $i < 3; $i++) {
            $checkDate = $now->copy()->subMonths($i);
            $period = $checkDate->format('Y-m');

            // Skip if already successfully processed
            if (AutoInterestRunLog::isProcessedFor($period)) {
                continue;
            }

            Log::info("Running catch-up for missed period: {$period}");
            $result = $this->runIfNeeded($period, force: false);
            $results[$period] = $result;

            // If any period failed, stop catch-up to avoid cascading failures
            if (isset($result['success']) && !$result['success']) {
                Log::warning("Catch-up stopped due to failure in period {$period}");
                break;
            }
        }

        return $results;
    }

    /**
     * Get last auto-run status
     */
    public function getLastRunStatus(): ?AutoInterestRunLog
    {
        return AutoInterestRunLog::where('status', 'success')
            ->orderBy('triggered_at', 'desc')
            ->first();
    }

    /**
     * Get current month status
     */
    public function getCurrentMonthStatus(): ?AutoInterestRunLog
    {
        $period = now()->format('Y-m');
        return AutoInterestRunLog::forPeriod($period)->first();
    }

    /**
     * Get last month period (for posting on tanggal 1)
     */
    public static function getLastMonthPeriod(): string
    {
        return now()->subMonth()->format('Y-m');
    }
}
