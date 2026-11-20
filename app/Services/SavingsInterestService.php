<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DailyInterestAccumulation;
use App\Models\Deposit;
use App\Models\Holiday;
use App\Models\InterestEngineLog;
use App\Models\InterestPostingLog;
use App\Models\InterestRate;
use App\Models\WorkdayYearCount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SavingsInterestService
{
    /**
     * Tanggal bunga simpanan satu periode dibukukan ke buku nasabah.
     * Ubah di sini saja kalau koperasi memindahkan jadwal postingnya.
     */
    public const POSTING_DAY = 5;

    /**
     * Tanggal posting untuk periode yang dimulai $periodStart: tanggal 5 bulan
     * berikutnya, atau hari kerja pertama sesudahnya kalau tanggal 5 jatuh di
     * hari Minggu / hari merah di Manajemen Hari Libur.
     *
     * Contoh: bunga periode Oktober 2026 dibukukan 5 November 2026.
     */
    public static function postingDateFor(Carbon $periodStart): Carbon
    {
        $nextMonth = $periodStart->copy()->startOfMonth()->addMonthNoOverflow();

        // min() supaya POSTING_DAY > 28 tidak luber ke bulan berikutnya.
        return Holiday::firstWorkdayOnOrAfter(
            $nextMonth->day(min(self::POSTING_DAY, $nextMonth->daysInMonth))
        );
    }

    /**
     * Jalankan proses hitung & posting bunga simpanan manual s.d. tanggal proses.
     *
     * @param string|null $processDateStr Format YYYY-MM-DD
     * @param int|null $userId User ID operator
     * @return array Summary hasil proses
     */
    public function processSavingsInterest(?string $processDateStr = null, ?int $userId = null, bool $force = false): array
    {
        $targetDate = $processDateStr ? Carbon::parse($processDateStr) : Carbon::today();

        // Bunga tidak boleh diakrual untuk hari yang belum terjadi. Tutup bulan
        // dipanggil dengan tanggal akhir bulan, yang bisa masih di masa depan.
        if ($targetDate->gt(Carbon::today())) {
            $targetDate = Carbon::today();
        }

        $dateStr = $targetDate->format('Y-m-d');

        // Validasi Rate Bunga Aktif
        $simpananRate = InterestRate::savings()->active()->where('is_default', true)
                ->where('effective_date', '<=', $dateStr)->orderBy('effective_date', 'desc')->first()
            ?? InterestRate::savings()->active()
                ->where('effective_date', '<=', $dateStr)->orderBy('effective_date', 'desc')->first();

        if (!$simpananRate) {
            throw new \RuntimeException('Tidak ditemukan konfigurasi suku bunga simpanan yang aktif pada Manajemen Bunga.');
        }

        $totalCustomersProcessed = 0;
        $totalInterestCalculated = 0;
        $processedDays = 0;
        $skippedDays = 0;
        $postedCount = 0;
        $totalPostedAmount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        DB::beginTransaction();
        try {
            // 1. Tentukan tanggal mulai perhitungan (misal dari H-30 atau last log)
            $lastEngineLog = InterestEngineLog::orderBy('run_date', 'desc')->first();
            if ($lastEngineLog) {
                $startDate = Carbon::parse($lastEngineLog->run_date)->addDay();
            } else {
                // Jika belum ada log sama sekali, cari tanggal transaksi simpanan tertua atau default 30 hari yang lalu
                $firstDeposit = Deposit::orderBy('created_at', 'asc')->first();
                $startDate = $firstDeposit ? Carbon::parse($firstDeposit->created_at)->startOfDay() : $targetDate->copy()->subDays(30);
            }

            if ($startDate->gt($targetDate)) {
                // Hari ini sudah pernah dihitung atau belum ada tanggal baru
                $startDate = $targetDate->copy();
            }

            // 2. Loop perhitungan per tanggal dari startDate s.d targetDate
            $currentDate = $startDate->copy();
            while ($currentDate->lte($targetDate)) {
                $cDateStr = $currentDate->format('Y-m-d');

                // Cek apakah tanggal ini sudah dihitung
                $alreadyLogged = InterestEngineLog::where('run_date', $cDateStr)->where('status', 'success')->exists();
                if ($alreadyLogged) {
                    $duplicateCount++;
                    $currentDate->addDay();
                    continue;
                }

                // Cek Libur
                if ($this->isHoliday($currentDate)) {
                    // upsert, bukan updateOrCreate: run_date unique, dan dua proses
                    // bersamaan pernah membuat seluruh transaksi rollback karena
                    // duplicate key (lihat auto_interest_run_logs periode 2026-10).
                    InterestEngineLog::upsert([[
                        'run_date' => $cDateStr,
                        'status' => 'skipped',
                        'reason' => 'Hari Libur / Minggu',
                        'total_customers' => 0,
                        'total_interest' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]], ['run_date'], ['status', 'reason', 'total_customers', 'total_interest', 'updated_at']);
                    $skippedDays++;
                    $currentDate->addDay();
                    continue;
                }

                // Hitung Bunga Harian
                $year = $currentDate->year;
                $workdayCount = WorkdayYearCount::where('year', $year)->value('workday_count') ?? WorkdayYearCount::recalculate($year);
                if ($workdayCount <= 0) {
                    $workdayCount = 260; // Fallback jika 0
                }

                $dailyInterestSum = 0;
                $dailyCustomersCount = 0;

                // Lock & process customer active
                $customers = Customer::where('status', 'active')->with('interestRate')->get();
                $inserts = [];

                foreach ($customers as $customer) {
                    $balances = DB::table('deposits')
                        ->selectRaw("
                            SUM(CASE WHEN type IN ('simpanan', 'bunga') THEN amount ELSE 0 END) as sum_masuk,
                            SUM(CASE WHEN type='penarikan' THEN amount ELSE 0 END) as sum_keluar
                        ")
                        ->where('customer_id', $customer->id)
                        ->whereDate('created_at', '<=', $cDateStr)
                        ->whereNull('deleted_at')
                        ->first();

                    $saldo = ($balances->sum_masuk ?? 0) - ($balances->sum_keluar ?? 0);

                    // Rate khusus nasabah hanya dipakai bila aktif dan sudah berlaku di
                    // tanggal hitung; aturan sama dengan InterestCalculateDaily.
                    $rate = $simpananRate;
                    if ($customer->interestRate
                        && $customer->interestRate->is_active
                        && $customer->interestRate->effective_date->format('Y-m-d') <= $cDateStr) {
                        $rate = $customer->interestRate;
                    }

                    if ($saldo > 0 && $rate && $rate->rate_percent > 0) {
                        // Formula Bunga Harian berdasarkan Bunga Tahunan / 12 / Hari Kerja:
                        // (Saldo * (rate_percent / 100) / 12) / (workdayCount / 12) -> equivalen Saldo * (rate / 100) / workdayCount
                        $bunga = (int) floor(($saldo * ($rate->rate_percent / 100)) / $workdayCount);
                        if ($bunga > 0) {
                            $inserts[] = [
                                'customer_id' => $customer->id,
                                'savings_type' => 'simpanan',
                                'calculation_date' => $cDateStr,
                                'base_balance' => $saldo,
                                'rate_percent' => $rate->rate_percent,
                                'interest_amount' => $bunga,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $dailyInterestSum += $bunga;
                            $dailyCustomersCount++;
                        }
                    }
                }

                if (count($inserts) > 0) {
                    DailyInterestAccumulation::upsert(
                        $inserts,
                        ['customer_id', 'calculation_date', 'savings_type'],
                        ['base_balance', 'rate_percent', 'interest_amount', 'updated_at']
                    );
                }

                InterestEngineLog::upsert([[
                    'run_date' => $cDateStr,
                    'status' => 'success',
                    'reason' => 'Diproses otomatis',
                    'total_customers' => $dailyCustomersCount,
                    'total_interest' => $dailyInterestSum,
                    'duration_seconds' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]], ['run_date'], ['status', 'reason', 'total_customers', 'total_interest', 'duration_seconds', 'updated_at']);

                $totalCustomersProcessed += $dailyCustomersCount;
                $totalInterestCalculated += $dailyInterestSum;
                $processedDays++;

                $currentDate->addDay();
            }

            // 3. Posting Bunga per Bulan (Aggregasi unposted accumulations ke transaksi simpanan per periode bulan)
            // Mengelompokkan per customer dan per bulan (YYYY-MM) agar 1 bulan tepat menjadi 1 baris transaksi bunga
            // Satu bulan dibukukan tepat sekali, pada TANGGAL 5 bulan berikutnya,
            // atau hari kerja sesudahnya kalau tanggal 5 jatuh di hari Minggu /
            // hari merah. Lihat self::postingDateFor().
            // Akrual bulan berjalan menumpuk di daily_interest_accumulations
            // sampai waktunya tiba. $force dipakai tombol tutup bulan manual manajer.
            $today = Carbon::today();

            $unpostedMonthlyAccumulations = DailyInterestAccumulation::where('is_posted', 0)
                ->selectRaw("customer_id, SUBSTR(calculation_date, 1, 7) as period, SUM(interest_amount) as total_interest, MAX(calculation_date) as last_calc_date")
                ->groupBy('customer_id', DB::raw("SUBSTR(calculation_date, 1, 7)"))
                ->get();

            if ($unpostedMonthlyAccumulations->isNotEmpty()) {
                $perPeriod = [];
                $postedPeriods = [];
                $postingDates = [];

                foreach ($unpostedMonthlyAccumulations as $acc) {
                    // Hari wajib ikut disebut: createFromFormat('Y-m', ...) memakai
                    // tanggal hari ini, jadi luber ke bulan berikutnya tiap tanggal 31.
                    $periodCarbon = Carbon::createFromFormat('Y-m-d', $acc->period . '-01')->startOfMonth();
                    // Sama untuk semua nasabah di periode yang sama; dihitung sekali
                    // supaya tidak memukul tabel holidays 70x per bulan.
                    $postingDate = $postingDates[$acc->period] ??= self::postingDateFor($periodCarbon);

                    // Belum ganti bulan / belum sampai tanggal postingnya: tunggu.
                    if (!$force && $today->lt($postingDate)) {
                        continue;
                    }

                    $postedPeriods[$acc->period] = $periodCarbon;

                    if ($acc->total_interest > 0) {
                        // Tanggal transaksi = hari bunga benar-benar masuk buku nasabah.
                        // Saat tutup bulan dipaksa manual, pakai hari ini.
                        $txnDate = ($force && $today->lt($postingDate) ? $today->copy() : $postingDate->copy())
                            ->setTime(23, 59, 59);

                        $periodLabel = $periodCarbon->isoFormat('MMMM Y');

                        Deposit::create([
                            'customer_id' => $acc->customer_id,
                            'type' => 'bunga',
                            'amount' => $acc->total_interest,
                            'previous_balance' => 0,
                            'current_balance' => 0,
                            'notes' => 'Bunga Simpanan Periode ' . $periodLabel,
                            'created_at' => $txnDate,
                            'updated_at' => now(),
                            'created_by' => $userId,
                        ]);

                        Deposit::recalculateBalance($acc->customer_id);

                        $postedCount++;
                        $totalPostedAmount += $acc->total_interest;

                        $perPeriod[$acc->period]['customers'] = ($perPeriod[$acc->period]['customers'] ?? 0) + 1;
                        $perPeriod[$acc->period]['interest'] = ($perPeriod[$acc->period]['interest'] ?? 0) + $acc->total_interest;
                    }
                }

                // Tandai hanya periode yang benar-benar dibukukan sebagai diposting;
                // bulan yang belum jatuh waktu posting harus tetap is_posted = 0.
                foreach ($postedPeriods as $periodStart) {
                    DailyInterestAccumulation::where('is_posted', 0)
                        ->whereBetween('calculation_date', [
                            $periodStart->copy()->startOfMonth()->format('Y-m-d'),
                            $periodStart->copy()->endOfMonth()->format('Y-m-d'),
                        ])
                        ->update([
                            'is_posted' => 1,
                            'posted_at' => now(),
                        ]);
                }

                // Log posting dicatat per periode bunga yang dibukukan, bukan per bulan
                // tanggal proses — guard bulanan di AutoInterestService bergantung pada ini.
                foreach ($perPeriod as $period => $totals) {
                    InterestPostingLog::updateOrCreate(
                        ['period' => $period],
                        [
                            'status' => 'success',
                            'total_customers' => $totals['customers'],
                            'total_interest' => $totals['interest'],
                            'posted_by' => $userId,
                            'notes' => 'Update bunga simpanan bulanan pada ' . now()->toDateTimeString(),
                        ]
                    );
                }
            }

            DB::commit();

            return [
                'success' => true,
                'process_date' => $dateStr,
                'processed_days' => $processedDays,
                'skipped_days' => $skippedDays,
                'total_customers' => $postedCount > 0 ? $postedCount : $totalCustomersProcessed,
                'posted_count' => $postedCount,
                'total_interest' => $totalPostedAmount > 0 ? $totalPostedAmount : $totalInterestCalculated,
                'duplicate' => $duplicateCount,
                'errors' => $errorCount,
                'message' => 'Proses update bunga simpanan berhasil dijalankan.',
            ];

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('SavingsInterestService Failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            throw $th;
        }
    }

    /**
     * Cek apakah tanggal tergolong hari libur
     */
    public function isHoliday(Carbon $date): bool
    {
        return !Holiday::isWorkday($date);
    }
}
