<?php

namespace App\Http\Middleware;

use App\Models\InterestEngineLog;
use App\Services\AutoInterestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        // Keluar sebelum menyentuh engine kalau hari ini sudah selesai. Middleware
        // ini jalan di SETIAP request user login, jadi jalur cepatnya harus murah.
        if (Cache::get('auto-interest:done') === now()->format('Y-m-d')) {
            return;
        }

        // Dua request bersamaan pernah membuat satu run rollback total karena
        // duplicate key, dan berisiko menulis baris bunga dobel. Satu run saja.
        $lock = Cache::lock('auto-interest:run', 120);

        if (!$lock->get()) {
            return;
        }

        try {
            $today = now()->format('Y-m-d');

            // 1. Pembayaran bunga deposito yang mencapai milestone bulanannya.
            $deposit = $this->autoInterestService->runDailyDepositCheck($today);

            // 2. Akrual bunga simpanan harian. Posting ke buku nasabah ditahan
            //    SavingsInterestService sampai bulannya tutup.
            $accrual = $this->autoInterestService->runDailyAccrual($today);

            // 3. Posting bunga simpanan bulan lalu bila belum dibukukan.
            $monthly = $this->autoInterestService->runMonthlySavingsPostingIfNeeded();

            // Jalur cepat hanya dipasang kalau semuanya memang beres, supaya
            // kegagalan tetap dicoba lagi di request berikutnya.
            $settled = ($deposit['success'] ?? false)
                && ($accrual['success'] ?? false)
                && (($monthly['already_done'] ?? false) || ($monthly['success'] ?? false));

            if ($settled && InterestEngineLog::where('run_date', $today)->exists()) {
                Cache::put('auto-interest:done', $today, now()->endOfDay());
            }
        } catch (\Exception $e) {
            // Silently log error, do not break the request flow
            Log::error('AutoPostInterest middleware failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        } finally {
            $lock->release();
        }
    }
}
