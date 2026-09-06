<?php

namespace App\Http\Middleware;

use App\Models\AutoInterestRunLog;
use App\Services\AutoInterestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AutoPostInterest
{
    protected AutoInterestService $autoInterestService;

    public function __construct(AutoInterestService $autoInterestService)
    {
        $this->autoInterestService = $autoInterestService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Only trigger for authenticated users
        if (!Auth::check()) {
            return $response;
        }

        // Register a callback to run after response is sent
        if (method_exists($response, 'setCallback')) {
            $response->setCallback(function () {
                $this->triggerAutoPostIfNeeded();
            });
        } else {
            // Fallback for older Laravel versions or when callback not available
            // Run in terminate phase
            app()->terminating(function () {
                $this->triggerAutoPostIfNeeded();
            });
        }

        return $response;
    }

    /**
     * Terminate phase: Run after response sent
     */
    public function terminate(Request $request, $response): void
    {
        // Double-check auth is available
        if (Auth::check()) {
            $this->triggerAutoPostIfNeeded();
        }
    }

    /**
     * Trigger auto-posting if conditions are met
     */
    protected function triggerAutoPostIfNeeded(): void
    {
        try {
            $now = now();

            // Only trigger on the 1st of the month
            if ($now->day !== 1) {
                // But also check for catch-up on other days if there are missed periods
                $this->checkForMissedPeriods();
                return;
            }

            // Get the period to post (last month, since we're on the 1st)
            $period = AutoInterestService::getLastMonthPeriod();

            Log::info("Auto interest middleware triggered on day 1", ['period' => $period]);

            // Check if already processed for this period
            if (AutoInterestRunLog::isProcessedFor($period)) {
                Log::debug("Auto interest already processed for period {$period}");
                return;
            }

            // Run the auto-posting
            $result = $this->autoInterestService->runIfNeeded($period);

            if (isset($result['success']) && $result['success']) {
                Log::info("Auto interest posting successful", ['period' => $period, 'result' => $result]);
            } else {
                Log::warning("Auto interest posting failed", ['period' => $period, 'result' => $result]);
            }
        } catch (\Exception $e) {
            // Catch all exceptions to prevent middleware from breaking the app
            Log::error("Auto interest middleware error: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Check for periods that were missed and run catch-up
     * This handles cases where tanggal 1 was not accessed (long holidays, etc.)
     */
    protected function checkForMissedPeriods(): void
    {
        try {
            // Check if there are any periods older than today that haven't been processed
            $now = now();
            $currentPeriod = $now->format('Y-m');
            $lastMonthPeriod = $now->subMonth()->format('Y-m');

            // Check if last month was processed
            if (!AutoInterestRunLog::isProcessedFor($lastMonthPeriod) &&
                !AutoInterestRunLog::pending()->forPeriod($lastMonthPeriod)->exists()) {

                // This means last month was completely missed
                Log::warning("Detected missed interest posting period, running catch-up", [
                    'missed_period' => $lastMonthPeriod,
                ]);

                // Only run catch-up on certain times to prevent excessive runs
                // Run only between 06:00-07:00 to limit overhead
                if ($now->hour === 6 && rand(1, 100) <= 20) {
                    $this->autoInterestService->runCatchUp();
                }
            }
        } catch (\Exception $e) {
            Log::error("Catch-up check failed: {$e->getMessage()}", ['exception' => $e]);
        }
    }
}
