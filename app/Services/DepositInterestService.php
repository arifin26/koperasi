<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\DepositInterestPayment;
use App\Models\FixedDeposit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositInterestService
{
    /**
     * Jalankan proses update/pembayaran bunga deposito manual.
     *
     * @param string|null $processDateStr
     * @param int|null $userId
     * @return array
     */
    public function processDepositInterest(?string $processDateStr = null, ?int $userId = null, bool $dryRun = false): array
    {
        $targetDate = $processDateStr ? Carbon::parse($processDateStr) : Carbon::today();
        $dateStr = $targetDate->format('Y-m-d');
        $period = $targetDate->format('Y-m');

        DB::beginTransaction();
        try {
            // Ambil seluruh deposito aktif atau yang sudah jatuh tempo namun belum selesai diproses
            $deposits = FixedDeposit::whereIn('status', ['active', 'matured'])
                ->with('customer')
                ->get();

            $totalChecked = $deposits->count();
            $maturedCount = 0;
            $processedCount = 0;
            $skippedCount = 0;
            $duplicateCount = 0;
            $errorCount = 0;
            $totalInterestAmount = 0;
            $paid = [];

            foreach ($deposits as $deposit) {
                // Cek apakah deposito sudah mencapai tanggal jatuh tempo (keseluruhan)
                $isMatured = $targetDate->gte($deposit->maturity_date);
                if ($isMatured) {
                    $maturedCount++;
                    if ($deposit->status === 'active') {
                        $deposit->status = 'matured';
                        $deposit->matured_at = now();
                        $deposit->save();
                    }
                }

                // Bunga hanya jadi hak nasabah selama masa deposito: bulan pertama
                // dibayar satu bulan setelah start_date, terakhir pada bulan jatuh
                // tempo. Di luar rentang itu deposito tidak berbunga lagi.
                if ($period <= $deposit->start_date->format('Y-m') || $period > $deposit->maturity_date->format('Y-m')) {
                    $skippedCount++;
                    continue;
                }

                // Cek apakah sudah pernah dibayar untuk periode bulan ini ($period)
                $alreadyPaid = DepositInterestPayment::where('fixed_deposit_id', $deposit->id)
                    ->where('period', $period)
                    ->exists();

                if ($alreadyPaid) {
                    $duplicateCount++;
                    $skippedCount++;
                    continue;
                }

                // Cek apakah hari ini sudah mencapai tanggal milestone bulanan deposito.
                // Contoh: start_date 15 Jan, maka tanggal 15 setiap bulan bunga berhak diproses.
                // Hari milestone dipangkas ke akhir bulan supaya deposito tanggal 29-31
                // tetap terbayar di Februari atau bulan 30 hari.
                $milestoneDay = min((int) $deposit->start_date->format('d'), $targetDate->daysInMonth);

                if ((int) $targetDate->format('d') < $milestoneDay && !$isMatured) {
                    $skippedCount++;
                    continue;
                }

                $interestAmount = (int) floor($deposit->amount * ($deposit->rate_percent / 100) / 12);
                if ($interestAmount <= 0) {
                    $skippedCount++;
                    continue;
                }

                $paid[] = [
                    'number' => $deposit->number,
                    'customer' => $deposit->customer->name ?? null,
                    'period' => $period,
                    'interest_amount' => $interestAmount,
                ];

                if ($dryRun) {
                    $processedCount++;
                    $totalInterestAmount += $interestAmount;
                    continue;
                }

                // Buat transaksi simpanan untuk bunga deposito
                $txn = Deposit::create([
                    'customer_id' => $deposit->customer_id,
                    'type' => 'bunga',
                    'amount' => $interestAmount,
                    'previous_balance' => 0,
                    'current_balance' => 0,
                    'notes' => 'Bunga Deposito ' . $deposit->number . ' Periode ' . $period,
                    'created_by' => $userId,
                    'created_at' => $targetDate->copy()->setTime(now()->hour, now()->minute, now()->second),
                ]);

                Deposit::recalculateBalance($deposit->customer_id);

                // Record pembayaran bunga deposito
                DepositInterestPayment::create([
                    'fixed_deposit_id' => $deposit->id,
                    'period' => $period,
                    'interest_amount' => $interestAmount,
                    'savings_txn_id' => $txn->id,
                    'paid_at' => now(),
                ]);

                $processedCount++;
                $totalInterestAmount += $interestAmount;
            }

            $dryRun ? DB::rollBack() : DB::commit();

            return [
                'success' => true,
                'dry_run' => $dryRun,
                'paid' => $paid,
                'process_date' => $dateStr,
                'total_checked' => $totalChecked,
                'matured_count' => $maturedCount,
                'processed_count' => $processedCount,
                'skipped_count' => $skippedCount,
                'duplicate_count' => $duplicateCount,
                'error_count' => $errorCount,
                'total_interest' => $totalInterestAmount,
                'message' => 'Proses update bunga deposito berhasil dijalankan.',
            ];

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('DepositInterestService Failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            throw $th;
        }
    }
}
