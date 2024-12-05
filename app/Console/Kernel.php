<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
//    protected function schedule(Schedule $schedule)
//    {
//
//        // Schedule the custom command to run every minute
//        $schedule->command('send:notistory')->everyFiveMinutes()->before(function () {
//            Log::info('Scheduler is running...'.now());
//        });
//    }

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:notistory')
            ->everyFiveMinutes()
            ->before(function () {
                Log::info('Scheduler triggered at ' . now());
            });
    }


    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}