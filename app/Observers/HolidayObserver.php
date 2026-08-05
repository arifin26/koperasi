<?php

namespace App\Observers;

use App\Models\Holiday;
use App\Models\WorkdayYearCount;
use Carbon\Carbon;

class HolidayObserver
{
    /**
     * Handle the Holiday "created" event.
     */
    public function created(Holiday $holiday): void
    {
        $this->recalculate($holiday);
    }

    /**
     * Handle the Holiday "updated" event.
     */
    public function updated(Holiday $holiday): void
    {
        $this->recalculate($holiday);
        
        // If year changed, recalculate the old year too
        if ($holiday->isDirty('year') && $holiday->getOriginal('year')) {
            WorkdayYearCount::recalculate($holiday->getOriginal('year'));
        }
    }

    /**
     * Handle the Holiday "deleted" event.
     */
    public function deleted(Holiday $holiday): void
    {
        $this->recalculate($holiday);
    }

    /**
     * Handle the Holiday "restored" event.
     */
    public function restored(Holiday $holiday): void
    {
        $this->recalculate($holiday);
    }

    private function recalculate(Holiday $holiday)
    {
        $year = $holiday->year ?? Carbon::parse($holiday->date)->year;
        WorkdayYearCount::recalculate($year);
    }
}
