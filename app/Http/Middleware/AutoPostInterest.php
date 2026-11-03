<?php

namespace App\Http\Middleware;

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

        // Register a terminating callback to run asynchronously after response is sent
        app()->terminating(function () {
            $this->triggerAutoPostIfNeeded();
        });

        return $response;
    }

    /**
     * Terminate phase: Run after response sent to user
     */
    public function terminate(Request $request, $response): void
    {
        if (Auth::check()) {
            $this->triggerAutoPostIfNeeded();
        }
    }

    /**
     * Trigger auto-posting for deposit and savings interest
     */
    protected function triggerAutoPostIfNeeded(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            $now = now();
            $today = $now->format('Y-m-d');

            // 1. Daily check for deposit interest payments (runs daily)
            $this->autoInterestService->runDailyDepositCheck($today);

            // 2. Check and run monthly savings interest if it's the 1st of the month
            $this->autoInterestService->runMonthlySavingsPostingIfNeeded();

            // 3. Catch-up for any missed previous periods
            $this->autoInterestService->runCatchUp();
        } catch (\Exception $e) {
            // Silently log error, do not break the request flow
            Log::error('AutoPostInterest middleware failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
