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
        // ***Schedule Frequency Options***
        // *---------------------------------*-----------------------------------------------------*
        // | Method                          | Description                                         |
        // | ->cron('* * * * * *');          | Run the task on a custom Cron schedule              |
        // | ->everyMinute();                | Run the task every minute                           |
        // | ->everyFiveMinutes();           | Run the task every five minutes                     |
        // | ->everyTenMinutes();            | Run the task every ten minutes                      |
        // | ->everyFifteenMinutes();        | Run the task every fifteen minutes                  |
        // | ->everyThirtyMinutes();         | Run the task every thirty minutes                   |
        // | ->hourly();                     | Run the task every hour                             |
        // | ->hourlyAt(17);                 | Run the task every hour at 17 mins past the hour    |
        // | ->daily();                      | Run the task every day at midnight                  |
        // | ->dailyAt('13:00');             | Run the task every day at 13:00                     |
        // | ->twiceDaily(1, 13);            | Run the task daily at 1:00 & 13:00                  |
        // | ->weekly();                     | Run the task every week                             |
        // | ->monthly();                    | Run the task every month                            |
        // | ->monthlyOn(4, '15:00');        | Run the task every month on the 4th at 15:00        |
        // | ->quarterly();                  | Run the task every quarter                          |
        // | ->yearly();                     | Run the task every year                             |
        // | ->timezone('America/New_York'); | Set the timezone                                    |
        // *---------------------------------*-----------------------------------------------------*

        // Sequence by time execute

        // $schedule->command('checkwithdrawalpixcelcoin')->cron('*/3 * * * *')->appendOutputTo('/var/www/html/fastpayments/logs/checkwithdrawalpixcelcoin.txt');
        // $schedule->command('executeallwithdrawals')->cron('*/3 * * * *')->appendOutputTo('/var/www/html/fastpayments/logs/executeallwithdrawal.txt');
        // $schedule->command('getstatuspixasaas:cron')->cron('*/1 * * * *')->appendOutputTo('/var/www/html/fastpayments/logs/checkstatuspixasas.txt');

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
