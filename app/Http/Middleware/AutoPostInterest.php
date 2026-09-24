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
        try {
            $now = now();
            $today = $now->format('Y-m-d');

            // 1. CEK HARIAN DEPOSITO (Setiap hari mengecek bunga deposito yang jatuh tempo pada tanggal pendaftarannya)
            $this->autoInterestService->runDailyDepositCheck($today);

            // 2. CEK BULANAN SIMPANAN (Setiap tanggal 1 memposting bunga simpanan bulan lalu, atau catch-up jika terlewat)
            if ($now->day === 1) {
                $lastMonthPeriod = AutoInterestService::getLastMonthPeriod();
                $this->autoInterestService->runMonthlySavingsPostingIfNeeded($lastMonthPeriod);
            } else {
                // Catch-up jika tanggal 1 kemarin libur / tidak ada aktivitas
                $this->autoInterestService->runCatchUp();
            }
        } catch (\Exception $e) {
            // Tangkap semua exception agar tidak pernah mengganggu response HTTP
            Log::error("Auto interest middleware error: {$e->getMessage()}", [
                'exception' => $e,
            ]);
        }
    }
}
