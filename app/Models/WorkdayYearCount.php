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
     * Workdays = (Total weekdays in year) - (National holidays on weekdays) + (Workdays overriding weekends)
     */
    public static function recalculate($year)
    {
        $startOfYear = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);
        
        // Count total weekdays in the year
        $weekdays = 0;
        $current = $startOfYear->copy();
        while($current <= $endOfYear) {
            if ($current->isWeekday()) {
                $weekdays++;
            }
            $current->addDay();
        }

        // Count holidays that fall on a weekday
        $holidaysOnWeekdays = Holiday::where('year', $year)
            ->where('type', 'holiday')
            ->get()
            ->filter(function($holiday) {
                return Carbon::parse($holiday->date)->isWeekday();
            })->count();

        // Count workdays that fall on a weekend (override)
        $workdaysOnWeekends = Holiday::where('year', $year)
            ->where('type', 'workday')
            ->get()
            ->filter(function($holiday) {
                return Carbon::parse($holiday->date)->isWeekend();
            })->count();

        $workdayCount = $weekdays - $holidaysOnWeekdays + $workdaysOnWeekends;

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
