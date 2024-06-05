<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CustomCommand::class,
        \App\Console\Commands\UpdateIntercom::class,
        \App\Console\Commands\UpdateDriverAvailability::class,
        \App\Console\Commands\UpdateDriverApproval::class,
        \App\Console\Commands\DriverMonthlyCommission::class,
        \App\Console\Commands\DriveNowPayment::class,
        
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('update:rides')
                 ->everyMinute(); 
        $schedule->command('update:intercom')
                ->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('month:commission')->dailyAt('08:00')->when(function () {
        return \Carbon\Carbon::now()->endOfMonth()->isToday();
        });
        $schedule->command('driver:drivenow')->everyMinute();
        $schedule->command('driver:approval')->dailyAt('08:00');
        $schedule->command('driver:availability')
                 ->everyMinute(); 


    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
