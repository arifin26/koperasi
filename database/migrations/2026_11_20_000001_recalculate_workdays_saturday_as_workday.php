<?php

use App\Models\WorkdayYearCount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sabtu kini dihitung sebagai hari kerja — hanya Minggu dan hari libur di
 * Manajemen Hari Libur yang tidak dihitung.
 *
 * workday_year_counts.workday_count adalah PEMBAGI bunga harian
 * (saldo x rate% / workday_count), jadi angkanya wajib dihitung ulang bersamaan
 * dengan perubahan aturan. Tanpa ini engine akan mengakrual di hari Sabtu tapi
 * tetap membagi dengan jumlah hari kerja yang lama, sehingga bunga setahun
 * membengkak sekitar 20%.
 */
return new class extends Migration
{
    public function up()
    {
        $years = DB::table('workday_year_counts')->pluck('year')->all();

        // Tahun berjalan ikut dihitung walau belum punya baris.
        $years = array_unique(array_merge($years, [(int) date('Y')]));

        foreach ($years as $year) {
            WorkdayYearCount::recalculate($year);
        }
    }

    public function down()
    {
        // Pembagi lama tidak disimpan; jalankan ulang recalculate() setelah
        // Holiday::isWeeklyOff() dikembalikan ke aturan Sabtu+Minggu.
    }
};
