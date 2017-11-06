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
        Commands\UnmatchedRequestsWithInterestCommand::class,
        Commands\UpdateRatingsUserCommand::class,
        Commands\UpdateCompletedRequestStatusCommand::class,
        Commands\InactiveMentorshipNotificationCommand::class

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
        $schedule->command("notify:unmatched-requests:success")->dailyAt("6:00");
        $schedule->command("cache:slack-users")->dailyAt("12:00");
        $schedule->command("notify:unapproved-sessions")->hourly();
        $schedule->command("notify:unmatched-requests:fellows")
            ->weekly()->tuesdays()->timezone("Africa/Lagos")->at("10:00");
        $schedule->command("notify:unmatched-requests:with-interests")
            ->dailyAt("12:00");
        $schedule->command("update:requests:completed")->dailyAt("12:00");
        $schedule->command("notify:inactive-mentorships")->dailyAt("9:00");
    }
}
