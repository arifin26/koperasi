<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyederhanakan jenis simpanan menjadi satu: 'simpanan'.
 *
 * - deposits.type      : wajib/sukarela/pokok  -> simpanan   (penarikan & bunga tetap)
 * - daily_interest_accumulations.savings_type : sukarela/wajib -> simpanan
 * - interest_rates.type: tabungan_*  -> simpanan, deposito_* -> deposito
 */
return new class extends Migration
{
    public function up()
    {
        // ------------------------------------------------------------------
        // 1. interest_rates — gabungkan jadi dua jenis: 'simpanan' & 'deposito'
        // ------------------------------------------------------------------
        // Tentukan dulu rate mana yang tetap aktif SEBELUM tipenya di-relabel,
        // karena setelah digabung tidak bisa dibedakan lagi asal-usulnya.
        $activeSimpananId = DB::table('interest_rates')
            ->whereIn('type', ['simpanan', 'tabungan_sukarela', 'tabungan_wajib'])
            ->where('is_active', 1)
            ->orderByRaw("FIELD(type, 'simpanan', 'tabungan_sukarela', 'tabungan_wajib')")
            ->orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->value('id');

        $activeDepositoId = DB::table('interest_rates')
            ->whereIn('type', ['deposito', 'deposito_12_bulan', 'deposito_6_bulan', 'deposito_3_bulan'])
            ->where('is_active', 1)
            ->orderByRaw("FIELD(type, 'deposito', 'deposito_12_bulan', 'deposito_6_bulan', 'deposito_3_bulan')")
            ->orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->value('id');

        DB::table('interest_rates')
            ->whereIn('type', ['tabungan_sukarela', 'tabungan_wajib'])
            ->update(['type' => 'simpanan']);

        DB::table('interest_rates')
            ->whereIn('type', ['deposito_3_bulan', 'deposito_6_bulan', 'deposito_12_bulan'])
            ->update(['type' => 'deposito']);

        // Sisakan tepat satu rate aktif per jenis; sisanya jadi riwayat.
        DB::table('interest_rates')
            ->whereIn('type', ['simpanan', 'deposito'])
            ->update(['is_active' => 0]);

        if ($activeSimpananId) {
            DB::table('interest_rates')->where('id', $activeSimpananId)->update(['is_active' => 1]);
        }

        if ($activeDepositoId) {
            DB::table('interest_rates')->where('id', $activeDepositoId)->update(['is_active' => 1]);
        }

        // ------------------------------------------------------------------
        // 2. deposits — pokok/wajib/sukarela dilebur jadi 'simpanan'
        // ------------------------------------------------------------------
        // Dilonggarkan ke VARCHAR dulu supaya nilai baru bisa ditulis, baru
        // dikunci lagi sebagai ENUM di akhir.
        DB::statement("ALTER TABLE deposits MODIFY type VARCHAR(20) NOT NULL DEFAULT 'simpanan'");

        DB::table('deposits')
            ->whereIn('type', ['pokok', 'wajib', 'sukarela'])
            ->update(['type' => 'simpanan']);

        // 'bunga_deposito' sempat dipakai di beberapa query lama.
        DB::table('deposits')
            ->where('type', 'bunga_deposito')
            ->update(['type' => 'bunga']);

        // 'bunga' sebelumnya ditulis engine tanpa pernah masuk daftar ENUM.
        DB::statement("ALTER TABLE deposits MODIFY type ENUM('simpanan', 'penarikan', 'bunga') NOT NULL DEFAULT 'simpanan'");

        // ------------------------------------------------------------------
        // 3. daily_interest_accumulations — sukarela + wajib jadi 'simpanan'
        // ------------------------------------------------------------------
        DB::statement("ALTER TABLE daily_interest_accumulations MODIFY savings_type VARCHAR(20) NOT NULL");

        // Unique key uq_customer_date_type (customer_id, calculation_date,
        // savings_type) akan bentrok kalau satu nasabah punya baris sukarela
        // DAN wajib di tanggal yang sama, jadi digabung dulu jadi satu baris.
        $duplicates = DB::table('daily_interest_accumulations')
            ->select('customer_id', 'calculation_date')
            ->groupBy('customer_id', 'calculation_date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('daily_interest_accumulations')
                ->where('customer_id', $duplicate->customer_id)
                ->where('calculation_date', $duplicate->calculation_date)
                ->orderBy('id')
                ->get();

            $keep = $rows->first();

            DB::table('daily_interest_accumulations')
                ->where('id', $keep->id)
                ->update([
                    'base_balance'    => $rows->sum('base_balance'),
                    'interest_amount' => $rows->sum('interest_amount'),
                    'is_posted'       => $rows->max('is_posted'),
                    'posted_at'       => $rows->max('posted_at'),
                    'updated_at'      => now(),
                ]);

            DB::table('daily_interest_accumulations')
                ->whereIn('id', $rows->slice(1)->pluck('id'))
                ->delete();
        }

        DB::table('daily_interest_accumulations')->update(['savings_type' => 'simpanan']);

        DB::statement("ALTER TABLE daily_interest_accumulations MODIFY savings_type ENUM('simpanan') NOT NULL DEFAULT 'simpanan'");
    }

    public function down()
    {
        // Peleburan data tidak bisa dipulihkan — kolom dikembalikan ke definisi
        // lama dengan seluruh baris dipetakan ke 'sukarela'.
        DB::statement("ALTER TABLE deposits MODIFY type VARCHAR(20) NOT NULL DEFAULT 'sukarela'");
        DB::table('deposits')->whereIn('type', ['simpanan', 'bunga'])->update(['type' => 'sukarela']);
        DB::statement("ALTER TABLE deposits MODIFY type ENUM('wajib', 'sukarela', 'pokok', 'penarikan') NOT NULL DEFAULT 'sukarela'");

        DB::statement("ALTER TABLE daily_interest_accumulations MODIFY savings_type VARCHAR(20) NOT NULL");
        DB::table('daily_interest_accumulations')->update(['savings_type' => 'sukarela']);
        DB::statement("ALTER TABLE daily_interest_accumulations MODIFY savings_type ENUM('sukarela', 'wajib') NOT NULL");

        DB::table('interest_rates')->where('type', 'simpanan')->update(['type' => 'tabungan_sukarela']);
        DB::table('interest_rates')->where('type', 'deposito')->update(['type' => 'deposito_12_bulan']);
    }
};
