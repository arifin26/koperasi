<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\InterestRate;
use App\Models\Holiday;
use App\Models\WorkdayYearCount;
use App\Models\DailyInterestAccumulation;
use App\Models\InterestEngineLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterestCalculateDaily extends Command
{
    protected $signature = 'interest:calculate-daily {--date= : The date to calculate for (YYYY-MM-DD), defaults to yesterday} {--dry-run : Simulate without saving}';
    protected $description = 'Menghitung akumulasi bunga harian untuk semua nasabah';

    public function handle()
    {
        $startTime = microtime(true);
        $dateStr = $this->option('date') ?? date('Y-m-d', strtotime('yesterday'));
        $date = Carbon::parse($dateStr);
        $dryRun = $this->option('dry-run');

        $this->info("Starting daily interest calculation for $dateStr");

        // 1. Cek apakah ini hari libur
        if ($this->isHoliday($date)) {
            $reason = 'Hari Libur / Weekend';
            $this->info("Skipping: $reason");
            if (!$dryRun) {
                InterestEngineLog::updateOrCreate(
                    ['run_date' => $dateStr],
                    [
                        'status' => 'skipped',
                        'reason' => $reason
                    ]
                );
            }
            return 0;
        }

        // 2. Ambil Rate Bunga
        $simpananRate = InterestRate::where('type', 'simpanan')->where('is_active', 1)->first();

        if (!$simpananRate) {
            $this->error('No active interest rate found for simpanan.');
            if (!$dryRun) {
                InterestEngineLog::updateOrCreate(
                    ['run_date' => $dateStr],
                    [
                        'status' => 'failed',
                        'reason' => 'No active interest rates found',
                        'error_message' => 'Jenis rate "simpanan" tidak punya rate aktif.'
                    ]
                );
            }
            return 1;
        }

        // 3. Ambil jumlah hari kerja tahun ini
        $year = $date->year;
        $workdayCount = WorkdayYearCount::where('year', $year)->value('workday_count') ?? WorkdayYearCount::recalculate($year);

        $totalCustomersProcessed = 0;
        $totalInterestCalculated = 0;

        // 4. Proses per nasabah menggunakan chunk (batch processing)
        Customer::where('status', 'active')->with('interestRate')->chunkById(100, function ($customers) use ($date, $dateStr, $workdayCount, $simpananRate, &$totalCustomersProcessed, &$totalInterestCalculated, $dryRun) {
            $inserts = [];

            foreach ($customers as $customer) {
                // Hitung saldo riil s.d tanggal kemarin via SQL.
                // Saldo = setoran simpanan + bunga yang sudah diposting - penarikan.
                $balances = DB::table('deposits')
                    ->selectRaw("
                        SUM(CASE WHEN type IN ('simpanan', 'bunga') THEN amount ELSE 0 END) as sum_masuk,
                        SUM(CASE WHEN type='penarikan' THEN amount ELSE 0 END) as sum_keluar
                    ")
                    ->where('customer_id', $customer->id)
                    ->whereDate('created_at', '<=', $dateStr)
                    ->whereNull('deleted_at')
                    ->first();

                $saldo = ($balances->sum_masuk ?? 0) - ($balances->sum_keluar ?? 0);

                // Tentukan rate untuk nasabah ini (Gunakan rate pribadi jika ada, jika tidak fallback ke global)
                $rate = $customer->interestRate ?? $simpananRate;

                if ($saldo > 0 && $rate) {
                    $bunga = floor(($saldo * ($rate->rate_percent / 100)) / $workdayCount);
                    if ($bunga > 0) {
                        $inserts[] = [
                            'customer_id' => $customer->id,
                            'savings_type' => 'simpanan',
                            'calculation_date' => $dateStr,
                            'base_balance' => $saldo,
                            'rate_percent' => $rate->rate_percent,
                            'interest_amount' => $bunga,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $totalInterestCalculated += $bunga;
                    }

                    $totalCustomersProcessed++;
                }
            }

            if (!$dryRun && count($inserts) > 0) {
                // Bulk insert with upsert (ON DUPLICATE KEY UPDATE id=id) to ensure idempotency
                DailyInterestAccumulation::upsert(
                    $inserts,
                    ['customer_id', 'calculation_date', 'savings_type'],
                    ['base_balance', 'rate_percent', 'interest_amount', 'updated_at']
                );
            }
        });

        $duration = (int)(microtime(true) - $startTime);

        if (!$dryRun) {
            InterestEngineLog::updateOrCreate(
                ['run_date' => $dateStr],
                [
                    'status' => 'success',
                    'reason' => 'Processed normally',
                    'total_customers' => $totalCustomersProcessed,
                    'total_interest' => $totalInterestCalculated,
                    'duration_seconds' => $duration
                ]
            );
        }

        $this->info("Completed. $totalCustomersProcessed customers processed. Total Interest: Rp $totalInterestCalculated");
        return 0;
    }

    private function isHoliday(Carbon $date)
    {
        $holiday = Holiday::where('date', $date->format('Y-m-d'))->first();
        if ($holiday) {
            return $holiday->type == 'holiday';
        }
        return $date->isWeekend();
    }
}
