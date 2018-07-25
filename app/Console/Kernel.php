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
        Commands\DecodeCredentialsCommand::class,
        Commands\UnmatchedRequestsFellowsCommand::class,
        Commands\EncodeCredentialsCommand::class,
        Commands\UpdateRatingsUserCommand::class,
        Commands\UpdateCompletedRequestStatusCommand::class,
        Commands\InactiveMentorshipNotificationCommand::class,
        Commands\DumpRequestTableCommand::class,
        Commands\RestoreRequestDataFromBackupCommand::class,
        Commands\RemodelMenteeMentorRequestRelationshipCommand::class,
        Commands\CacheUsersAverageRatingCommand::class,
        Commands\UnmatchedRequestNotificationCommand::class,


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
//        $schedule->command("notify:unapproved-sessions")->weekly()->tuesdays()->timezone("Africa/Lagos")->at("10:00")->onOneServer();
//        $schedule->command("notify:unmatched-requests:with-interests")
//            ->dailyAt("12:00")->onOneServer();
//        $schedule->command("notify:inactive-mentorships")->dailyAt("9:00")->onOneServer();

//        $schedule->command("notify:unmatched-requests:success")
//            ->dailyAt("6:00")
//            ->onOneServer();
//
//        $schedule->command("notify:unmatched-fellow-requests")
//            ->dailyAt("12:00")
//            ->onOneServer();
//        $schedule->command("notify:unmatched-requests:fellows")
//            ->weekly()
//            ->tuesdays()
//            ->timezone("Africa/Lagos")
//            ->at("10:00");
//        $schedule->command("cache:slack-users")
//            ->dailyAt("12:00")
//            ->onOneServer();
//
//        $schedule->command("update:requests:completed")
//            ->dailyAt("12:00")
//            ->onOneServer();
//
//        $schedule->command("cache:user-average-rating")
//            ->dailyAt("12:00")
//            ->onOneServer();
    }
}
