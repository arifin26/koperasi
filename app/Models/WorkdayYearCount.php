<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkdayYearCount extends Model
{
    protected $guarded = ["id"];

    protected $casts = [
        "calculated_at" => "datetime",
    ];

    /**
     * Helper function to recalculate workdays for a specific year.
     * Workdays = (Total weekdays in year) - (National holidays on weekdays) + (Workdays overriding weekends)
     * FIXED (K5): Now only excludes Sunday (not both Sat+Sun)
     * Workdays are: Mon-Sat (excluding Sunday)
     */
    public static function recalculate($year)
    {
        $startOfYear = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);

        // Count all days that are NOT Sunday (workdays: Mon-Sat)
        $workdays = 0;
        $current = $startOfYear->copy();
        while ($current <= $endOfYear) {
            if (!$current->isSunday()) {
                $workdays++;
            }
            $current->addDay();
        }

        // Subtract holidays that fall on workdays (Mon-Sat, not Sunday)
        $holidaysOnWorkdays = Holiday::where("year", $year)
            ->where("type", "holiday")
            ->get()
            ->filter(function ($holiday) {
                $date = Carbon::parse($holiday->date);
                return !$date->isSunday(); // Holidays on Mon-Sat
            })->count();

        // Add workdays that fall on weekends (override - only applies to Sat since Sun is not a workday anyway)
        $workdaysOnWeekends = Holiday::where("year", $year)
            ->where("type", "workday")
            ->get()
            ->filter(function ($holiday) {
                $date = Carbon::parse($holiday->date);
                return $date->isSunday(); // Workday override only on Sunday
            })->count();

        $workdayCount = $workdays - $holidaysOnWorkdays + $workdaysOnWeekends;

        self::updateOrCreate(
            ["year" => $year],
            [
                "workday_count" => $workdayCount,
                "calculated_at" => now(),
            ]
        );

        return $workdayCount;
    }
}
