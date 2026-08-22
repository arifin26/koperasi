<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // PROSES BUNGA SEKARANG DILAKUKAN SECARA MANUAL VIA BUTTON (TIDAK MENGGUNAKAN SCHEDULER)
        // Command CLI tetap tersedia untuk kebutuhan dev/maintenance jika diperlukan:
        // - php artisan interest:calculate-daily
        // - php artisan interest:post-monthly
        // - php artisan deposit:pay-interest
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
