<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FixedDeposit;
use App\Models\DepositInterestPayment;
use App\Models\Deposit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositPayInterest extends Command
{
    protected $signature = 'deposit:pay-interest {--date= : Override tanggal (YYYY-MM-DD)} {--dry-run : Simulasi tanpa menyimpan}';
    protected $description = 'Transfer bunga deposito ke tabungan sukarela nasabah (bulanan)';

    public function handle()
    {
        $dateStr = $this->option('date') ?? date('Y-m-d');
        $date = Carbon::parse($dateStr);
        $dryRun = $this->option('dry-run');
        $dayOfMonth = $date->day;
        $period = $date->format('Y-m');

        $this->info("Checking deposit interest payments for date: $dateStr (day: $dayOfMonth)");

        // Find all active deposits where start_date day matches today's day
        $deposits = FixedDeposit::where('status', 'active')
            ->whereDay('start_date', $dayOfMonth)
            ->with('customer')
            ->get();

        if ($deposits->isEmpty()) {
            $this->info("No active deposits with registration day = $dayOfMonth.");
            return 0;
        }

        $totalPaid = 0;
        $totalAmount = 0;

        foreach ($deposits as $deposit) {
            // Check idempotency: already paid for this period?
            $exists = DepositInterestPayment::where('fixed_deposit_id', $deposit->id)
                ->where('period', $period)
                ->exists();

            if ($exists) {
                $this->line("  SKIP: {$deposit->number} — already paid for $period");
                continue;
            }

            $interestAmount = (int) floor($deposit->amount * ($deposit->rate_percent / 100) / 12);
            if ($interestAmount <= 0) {
                $this->line("  SKIP: {$deposit->number} — interest amount = 0");
                continue;
            }

            $this->line("  PAY: {$deposit->number} — Rp " . number_format($interestAmount) . " → {$deposit->customer->name}");

            if (!$dryRun) {
                DB::transaction(function () use ($deposit, $interestAmount, $period) {
                    // Create savings transaction (bunga deposito)
                    $txn = Deposit::create([
                        'customer_id' => $deposit->customer_id,
                        'type' => 'bunga',
                        'amount' => $interestAmount,
                        'previous_balance' => 0,
                        'current_balance' => 0,
                        'notes' => 'Bunga Deposito ' . $deposit->number . ' periode ' . $period,
                    ]);

                    Deposit::recalculateBalance($deposit->customer_id);

                    // Record payment
                    DepositInterestPayment::create([
                        'fixed_deposit_id' => $deposit->id,
                        'period' => $period,
                        'interest_amount' => $interestAmount,
                        'savings_txn_id' => $txn->id,
                        'paid_at' => now(),
                    ]);
                });
            }

            $totalPaid++;
            $totalAmount += $interestAmount;
        }

        $this->info("Done. $totalPaid deposits paid. Total: Rp " . number_format($totalAmount));
        return 0;
    }
}
