<?php

namespace App\Services;

use App\Models\AutoInterestRunLog;
use App\Models\InterestPostingLog;
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
     * Run daily deposit interest check & payout
     * Executed daily whenever users are active, paying any deposits that reached their anniversary day.
     *
     * @param string|null $processDateStr Format YYYY-MM-DD
     * @return array
     */
    public function runDailyDepositCheck(?string $processDateStr = null): array
    {
        $today = $processDateStr ?? now()->format('Y-m-d');
        try {
            $depositResult = $this->processDepositInterest($today);
            return [
                'success' => true,
                'date' => $today,
                'deposit' => $depositResult,
            ];
        } catch (\Exception $e) {
            Log::error("Daily deposit interest check failed for {$today}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            return [
                'success' => false,
                'date' => $today,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Akrual bunga simpanan harian.
     *
     * WAJIB dipanggil tiap hari, dan TIDAK boleh lewat
     * runMonthlySavingsPostingIfNeeded: guard bulanan di sana mengunci seluruh
     * bulan, sehingga akrual bisa berhenti berminggu-minggu (pernah terjadi:
     * tidak ada akrual sama sekali antara 2026-10-31 dan 2026-11-20).
     * Posting ke buku nasabah tetap ditahan SavingsInterestService sampai
     * bulannya tutup, jadi memanggil ini tiap hari aman.
     *
     * @param string|null $processDateStr Format YYYY-MM-DD
     * @return array
     */
    public function runDailyAccrual(?string $processDateStr = null): array
    {
        $today = $processDateStr ?? now()->format('Y-m-d');
        try {
            return [
                'success' => true,
                'date' => $today,
                'savings' => $this->processSavingsInterest($today),
            ];
        } catch (\Exception $e) {
            Log::error("Daily savings interest accrual failed for {$today}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            return [
                'success' => false,
                'date' => $today,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run monthly savings interest posting for the given period (default: last month)
     *
     * @param string|null $period Period in Y-m format (e.g., '2026-08')
     * @param bool $force Force run even if already processed
     * @return array
     */
    public function runMonthlySavingsPostingIfNeeded(?string $period = null, bool $force = false): array
    {
        $period = $period ?? self::getLastMonthPeriod();

        try {
            // Guard-nya HANYA InterestPostingLog, yang ditulis per periode dan hanya
            // kalau bunga benar-benar dibukukan. AutoInterestRunLog sengaja tidak
            // dipakai di sini: ia mencatat "sudah dijalankan", bukan "sudah
            // diposting", sehingga run tanggal 1 yang jatuh hari Minggu (belum
            // waktunya posting) akan mengunci periode itu selamanya. Run log tetap
            // ditulis di bawah sebagai jejak audit.
            $alreadyLogged = !$force
                && InterestPostingLog::where('period', $period)->where('status', 'success')->exists();

            if ($alreadyLogged) {
                return ['already_done' => true, 'period' => $period];
            }

            $log = AutoInterestRunLog::getOrCreatePending($period);

            // Hari wajib disebut: createFromFormat('Y-m', ...) memakai tanggal hari
            // ini, jadi tiap tanggal 31 ia luber ke bulan berikutnya.
            $periodDate = \Carbon\Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();
            $endOfPeriod = $periodDate->endOfMonth()->format('Y-m-d');

            $savingsResult = $this->processSavingsInterest($endOfPeriod, $force);
            $today = now()->format('Y-m-d');
            $depositResult = $this->processDepositInterest($today);

            $log->markSuccess($savingsResult, $depositResult);

            return [
                'success' => true,
                'period' => $period,
                'savings' => $savingsResult,
                'deposit' => $depositResult,
            ];
        } catch (\Exception $e) {
            Log::error("Monthly savings interest posting failed for period {$period}: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            if (isset($log)) {
                $log->markFailed($e->getMessage());
            }

            return [
                'success' => false,
                'period' => $period,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run auto-posting for the given period if needed (full workflow)
     */
    public function runIfNeeded(string $period, bool $force = false): array
    {
        return $this->runMonthlySavingsPostingIfNeeded($period, $force);
    }

    /**
     * Process savings interest posting for a date
     */
    protected function processSavingsInterest(string $processDateStr, bool $force = false): array
    {
        try {
            $result = $this->savingsService->processSavingsInterest($processDateStr, null, $force);
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
     *
     * TIDAK lagi dipanggil dari AutoPostInterest: memutar 3 bulan ke belakang di
     * setiap request terlalu mahal, dan sudah tidak perlu. runDailyAccrual
     * mengakrual balik dari interest_engine_logs terakhir, dan posting di
     * SavingsInterestService mengelompokkan SEMUA akumulasi yang belum diposting
     * per bulan, jadi bulan lama yang terlewat ikut terbukukan sendiri.
     * Disediakan untuk pemakaian manual/command.
     */
    public function runCatchUp(): array
    {
        $results = [];
        $now = now();

        // Check up to 3 months back for unprocessed periods
        for ($i = 1; $i <= 3; $i++) {
            $checkDate = $now->copy()->subMonths($i);
            $period = $checkDate->format('Y-m');

            // Sama seperti runMonthlySavingsPostingIfNeeded: hanya InterestPostingLog
            // yang menandakan bunga sudah benar-benar dibukukan.
            if (InterestPostingLog::where('period', $period)->where('status', 'success')->exists()) {
                continue;
            }

            Log::info("Running catch-up for missed savings interest period: {$period}");
            $result = $this->runMonthlySavingsPostingIfNeeded($period, force: false);
            $results[$period] = $result;

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
