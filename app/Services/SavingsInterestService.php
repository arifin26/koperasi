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
     * Jalankan proses hitung & posting bunga simpanan manual s.d. tanggal proses.
     *
     * @param string|null $processDateStr Format YYYY-MM-DD
     * @param int|null $userId User ID operator
     * @return array Summary hasil proses
     */
    public function processSavingsInterest(?string $processDateStr = null, ?int $userId = null): array
    {
        $targetDate = $processDateStr ? Carbon::parse($processDateStr) : Carbon::today();
        $dateStr = $targetDate->format('Y-m-d');

        // Validasi Rate Bunga Aktif
        $simpananRate = InterestRate::savings()->active()->where('is_default', true)->first()
            ?? InterestRate::savings()->active()->orderBy('effective_date', 'desc')->first();

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
                    InterestEngineLog::updateOrCreate(
                        ['run_date' => $cDateStr],
                        [
                            'status' => 'skipped',
                            'reason' => 'Hari Libur / Weekend',
                            'total_customers' => 0,
                            'total_interest' => 0,
                        ]
                    );
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
                    $rate = $customer->interestRate ?? $simpananRate;

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

                InterestEngineLog::updateOrCreate(
                    ['run_date' => $cDateStr],
                    [
                        'status' => 'success',
                        'reason' => 'Diproses manual',
                        'total_customers' => $dailyCustomersCount,
                        'total_interest' => $dailyInterestSum,
                        'duration_seconds' => 1
                    ]
                );

                $totalCustomersProcessed += $dailyCustomersCount;
                $totalInterestCalculated += $dailyInterestSum;
                $processedDays++;

                $currentDate->addDay();
            }

            // 3. Posting Bunga per Bulan (Aggregasi unposted accumulations ke transaksi simpanan per periode bulan)
            // Mengelompokkan per customer dan per bulan (YYYY-MM) agar 1 bulan tepat menjadi 1 baris transaksi bunga
            $unpostedMonthlyAccumulations = DailyInterestAccumulation::where('is_posted', 0)
                ->where('calculation_date', '<=', $dateStr)
                ->selectRaw("customer_id, DATE_FORMAT(calculation_date, '%Y-%m') as period, SUM(interest_amount) as total_interest, MAX(calculation_date) as last_calc_date")
                ->groupBy('customer_id', DB::raw("DATE_FORMAT(calculation_date, '%Y-%m')"))
                ->get();

            if ($unpostedMonthlyAccumulations->isNotEmpty()) {
                foreach ($unpostedMonthlyAccumulations as $acc) {
                    if ($acc->total_interest > 0) {
                        $periodCarbon = Carbon::createFromFormat('Y-m', $acc->period);
                        // Tanggal transaksi bunga dicatat pada akhir bulan periode tersebut (atau targetDate jika periode saat ini)
                        $txnDate = $periodCarbon->copy()->endOfMonth()->lte($targetDate)
                            ? $periodCarbon->copy()->endOfMonth()->setTime(23, 59, 59)
                            : $targetDate->copy()->setTime(23, 59, 59);

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
                    }
                }

                // Tandai seluruh akumulasi s.d tanggal ini sebagai sudah diposting
                DailyInterestAccumulation::where('is_posted', 0)
                    ->where('calculation_date', '<=', $dateStr)
                    ->update([
                        'is_posted' => 1,
                        'posted_at' => now(),
                    ]);

                // Record log posting
                InterestPostingLog::create([
                    'period' => $targetDate->format('Y-m') . '-' . date('d-His'),
                    'status' => 'manual',
                    'total_customers' => $postedCount,
                    'total_interest' => $totalPostedAmount,
                    'posted_by' => $userId,
                    'notes' => 'Update bunga simpanan bulanan pada ' . now()->toDateTimeString(),
                ]);
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
        $holiday = Holiday::where('date', $date->format('Y-m-d'))->first();
        if ($holiday) {
            return $holiday->type === 'holiday';
        }
        return $date->isWeekend();
    }
}
