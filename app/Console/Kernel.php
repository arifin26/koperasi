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
        // Engine Bunga Harian: setiap hari pukul 00:05 WIB
        $schedule->command('interest:calculate-daily')
                 ->dailyAt('00:05')
                 ->timezone('Asia/Jakarta')
                 ->withoutOverlapping()
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('interest:calculate-daily FAILED in scheduler');
                 });

        // Posting Bunga: setiap tanggal 1, pukul 00:30 WIB
        $schedule->command('interest:post-monthly')
                 ->monthlyOn(1, '00:30')
                 ->timezone('Asia/Jakarta')
                 ->withoutOverlapping();

        // Transfer Bunga Deposito: setiap hari pukul 01:00 WIB
        $schedule->command('deposit:pay-interest')
                 ->dailyAt('01:00')
                 ->timezone('Asia/Jakarta')
                 ->withoutOverlapping();
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
