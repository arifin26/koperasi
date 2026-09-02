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
    public function processDepositInterest(?string $processDateStr = null, ?int $userId = null): array
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

                // Bunga deposito diperhitungkan per bulan sesuai milestone tanggal pendaftaran (start_date)
                // Atau jika sudah jatuh tempo.
                // Cek apakah sudah pernah dibayar untuk periode bulan ini ($period)
                $alreadyPaid = DepositInterestPayment::where('fixed_deposit_id', $deposit->id)
                    ->where('period', $period)
                    ->exists();

                if ($alreadyPaid) {
                    $duplicateCount++;
                    $skippedCount++;
                    continue;
                }

                // Cek apakah hari ini sudah mencapai atau melewati tanggal milestone bulanan deposito
                // Contoh: start_date 15 Jan, maka tanggal 15 setiap bulan bunga berhak diproses
                $startDay = (int) $deposit->start_date->format('d');
                $targetDay = (int) $targetDate->format('d');

                // Jika belum mencapai milestone hari di bulan ini dan belum jatuh tempo, skip
                if ($targetDay < $startDay && !$isMatured) {
                    $skippedCount++;
                    continue;
                }

                $interestAmount = (int) floor($deposit->amount * ($deposit->rate_percent / 100) / 12);
                if ($interestAmount <= 0) {
                    $skippedCount++;
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

            DB::commit();

            return [
                'success' => true,
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
