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
        Commands\UnmatchedRequestsSuccessCommand::class,
        Commands\CacheSlackUsersCommand::class,
        Commands\UnapprovedSessionsReminderCommand::class,
        Commands\GenerateGoogleCredentials::class,
        Commands\UnmatchedRequestsFellowsCommand::class,
        Commands\EncryptGoogleCredentialsCommand::class,
        Commands\UpdateUsersSlackIdsCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule command schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('notify:unmatched-requests:success')->dailyAt('6:00');
        $schedule->command('cache:slack-users')->dailyAt('12:00');
        $schedule->command('notify:unapproved-sessions')->hourly();
        $schedule->command('notify:unmatched-requests:fellows')
            ->weekly()->mondays()->at('9:00');
    }

}
