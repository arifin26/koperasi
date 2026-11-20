<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkdayYearCount extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'calculated_at' => 'datetime',
    ];

    /**
     * Helper function to recalculate workdays for a specific year.
     *
     * Hari kerja = setiap tanggal yang lolos Holiday::isWorkday(): semua hari
     * kecuali Minggu dan hari libur, ditambah hari kerja pengganti. Sabtu ikut
     * dihitung. Angka ini jadi pembagi bunga harian, jadi wajib dihitung ulang
     * tiap kali data hari libur berubah (lihat HolidayObserver).
     */
    public static function recalculate($year)
    {
        $startOfYear = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);

        // Satu query, bukan satu per tanggal: Holiday::isWorkday() memukul DB
        // tiap pemanggilan dan ini memutar 365 hari.
        $types = [];
        foreach (Holiday::whereYear('date', $year)->get(['date', 'type']) as $holiday) {
            $types[Carbon::parse($holiday->date)->format('Y-m-d')] = $holiday->type;
        }

        $workdayCount = 0;
        $current = $startOfYear->copy();

        while ($current <= $endOfYear) {
            $override = $types[$current->format('Y-m-d')] ?? null;

            $isWorkday = $override !== null
                ? $override === 'workday'
                : !Holiday::isWeeklyOff($current);

            if ($isWorkday) {
                $workdayCount++;
            }

            $current->addDay();
        }

        self::updateOrCreate(
            ['year' => $year],
            [
                'workday_count' => $workdayCount,
                'calculated_at' => now(),
            ]
        );

        return $workdayCount;
    }
}
