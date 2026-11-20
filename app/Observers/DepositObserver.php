<?php

namespace App\Observers;

use App\Models\DailyInterestAccumulation;
use App\Models\Deposit;
use App\Models\InterestEngineLog;
use Carbon\Carbon;

class DepositObserver
{
    public function saved(Deposit $deposit): void
    {
        $this->invalidateInterestFrom($deposit);
    }

    public function deleted(Deposit $deposit): void
    {
        $this->invalidateInterestFrom($deposit);
    }

    public function restored(Deposit $deposit): void
    {
        $this->invalidateInterestFrom($deposit);
    }

    public function forceDeleted(Deposit $deposit): void
    {
        $this->invalidateInterestFrom($deposit);
    }

    /**
     * Transaksi backdate mengubah saldo historis, jadi akrual bunga sejak tanggal
     * itu sudah tidak sesuai dan harus dihitung ulang.
     *
     * Tanpa ini transaksi backdate tidak pernah dapat bunga sama sekali:
     * interest_engine_logs menandai tanggalnya sudah selesai, dan loop akrual di
     * SavingsInterestService melewati tanggal yang sudah berstatus success secara
     * permanen. (Terjadi pada deposits id 279-281: 33 juta diinput 2026-10-07
     * dengan tanggal Januari-April, tidak berbunga sepeser pun.)
     *
     * Dipasang sebagai observer karena ketujuh jalur tulis — store/update/destroy
     * di DepositController dan WithdrawalController, plus restore di
     * TrashController — semuanya lewat sini, sama seperti HolidayObserver.
     */
    private function invalidateInterestFrom(Deposit $deposit): void
    {
        // Baris 'bunga' dibuat oleh engine itu sendiri: jangan membatalkan hasil
        // yang baru saja dihitung.
        if ($deposit->type === 'bunga') {
            return;
        }

        $latestRun = InterestEngineLog::max('run_date');

        if (!$latestRun) {
            return;
        }

        $from = Carbon::parse($deposit->created_at)->format('Y-m-d');

        // shortcut: periode yang akumulasinya sudah dibukukan (is_posted = 1) tidak
        // dihitung ulang — baris bunga-nya sudah masuk buku tabungan nasabah, dan
        // membatalkannya bukan keputusan sistem. Perlu koreksi manual manajer.
        // Naikkan batas ini kalau nanti ada fitur pembatalan posting bunga.
        $lastPosted = DailyInterestAccumulation::where('is_posted', 1)->max('calculation_date');

        if ($lastPosted) {
            $floor = Carbon::parse($lastPosted)->addDay()->format('Y-m-d');
            $from = max($from, $floor);
        }

        if ($from > Carbon::parse($latestRun)->format('Y-m-d')) {
            return; // bukan backdate: akrual berikutnya sudah mencakup tanggal ini
        }

        DailyInterestAccumulation::where('is_posted', 0)
            ->where('calculation_date', '>=', $from)
            ->delete();

        InterestEngineLog::where('run_date', '>=', $from)->delete();
    }
}
