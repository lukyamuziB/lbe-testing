<?php
/**
 * File defines class for a console command to send
 * slack notifications to users
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Session;
use App\Utility\SlackUtility;
use App\Repositories\SlackUsersRepository;

/**
 * Class UnapprovedSessionsReminderCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class UnapprovedSessionsReminderCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "notify:unapproved-sessions";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sends a slack message about unapproved sessions";

    protected $slack_utility;
    protected $slack_repository;

    /**
     * UnapprovedSessionsReminderCommand constructor.
     *
     * @param SlackUtility $slack_utility SlackUtility slack utility client for API calls
     * @param SlackUsersRepository $slack_repository slack users repository
     */
    public function __construct(
        SlackUtility $slack_utility, SlackUsersRepository $slack_repository
    ) {
        parent::__construct();

        $this->slack_utility = $slack_utility;
        $this->slack_repository = $slack_repository;

    }

    /**
     * Execute console command
     */
    public function handle()
    {
        $lenken_base_url = getenv("LENKEN_FRONTEND_BASE_URL");

        $unapproved_sessions = Session::getUnApprovedSessionsByTime(2)
            ->toArray();

        // send slack notifications to users
        foreach ($unapproved_sessions as $session) {
            $session_url = "{$lenken_base_url}/requests/{$session['request_id']}";
            $user_key = $session["mentee_approved"] ? "mentor" : "user";
            $message
                = "A Mentorship Session has been logged with you.".
                   "\nKindly approve: {$session_url}";

            if (isset($session["request"][$user_key]["slack_id"])) {
                $this->slack_utility->sendMessage(
                    [$session["request"][$user_key]["slack_id"]],
                    $message
                );
            }
        }
    }
}
