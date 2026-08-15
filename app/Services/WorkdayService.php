<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkdayService
{
    /**
     * Check if a date is a workday (Monday-Saturday, excluding holidays)
     * Workdays: Mon-Sat (Sunday is always non-workday)
     * Non-workdays: Sunday, holidays (type=holiday), weekends (except Sat is workday)
     *
     * @param Carbon $date
     * @return bool
     */
    public static function isWorkday(Carbon $date): bool
    {
        // Sunday is always non-workday
        if ($date->isSunday()) {
            return false;
        }

        // Check if this date is in Holiday table
        $holiday = Holiday::where("date", $date->format("Y-m-d"))->first();
        
        if ($holiday) {
            // If it"s marked as "workday" (override), it"s a workday
            if ($holiday->type === "workday") {
                return true;
            }
            // If it"s marked as "holiday", it"s not a workday
            if ($holiday->type === "holiday") {
                return false;
            }
        }

        // Default: Mon-Sat (excluding Sunday) are workdays
        return $date->isWeekday() && !$date->isSunday();
    }

    /**
     * Get all workdays in a date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<Carbon>
     */
    public static function getWorkdaysInRange(Carbon $startDate, Carbon $endDate): array
    {
        $workdays = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if (self::isWorkday($current)) {
                $workdays[] = $current->copy();
            }
            $current->addDay();
        }

        return $workdays;
    }

    /**
     * Count workdays in a year
     *
     * @param int $year
     * @return int
     */
    public static function countWorkdaysInYear(int $year): int
    {
        $startOfYear = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);

        return count(self::getWorkdaysInRange($startOfYear, $endOfYear));
    }

    /**
     * Get workdays between two dates
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public static function countWorkdaysBetween(Carbon $startDate, Carbon $endDate): int
    {
        return count(self::getWorkdaysInRange($startDate, $endDate));
    }
}
