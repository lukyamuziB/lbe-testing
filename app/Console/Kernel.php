<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\UnmatchedRequestsCommand::class,
        Commands\CacheSlackUsersCommand::class,
        Commands\UnapprovedSessionsReminderCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('notify:unmatched-requests')->dailyAt('6:00');
        $schedule->command('cache:slack-users')->dailyAt('12:00');
        $schedule->command('notify:unapproved-sessions')->hourly();
    }

}
