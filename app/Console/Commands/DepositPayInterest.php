<?php

namespace App\Console\Commands;

use App\Services\DepositInterestService;
use Illuminate\Console\Command;

class DepositPayInterest extends Command
{
    protected $signature = 'deposit:pay-interest {--date= : Override tanggal (YYYY-MM-DD)} {--dry-run : Simulasi tanpa menyimpan}';
    protected $description = 'Transfer bunga deposito ke simpanan nasabah (bulanan)';

    public function handle(DepositInterestService $service)
    {
        $dateStr = $this->option('date') ?? date('Y-m-d');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Checking deposit interest payments for date: $dateStr");

        $result = $service->processDepositInterest($dateStr, null, $dryRun);

        foreach ($result['paid'] as $row) {
            $this->line("  PAY: {$row['number']} — Rp " . number_format($row['interest_amount'])
                . " (periode {$row['period']}) -> " . ($row['customer'] ?? '-'));
        }

        $this->info("Done. {$result['processed_count']} deposito dibayar, {$result['skipped_count']} dilewati. "
            . 'Total: Rp ' . number_format($result['total_interest']));

        if ($dryRun) {
            $this->info('Dry run — tidak ada data yang disimpan.');
        }

        return 0;
    }
}
