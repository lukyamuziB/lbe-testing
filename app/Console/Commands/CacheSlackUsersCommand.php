<?php
/**
 * File defines class for a console command to retrieve
 * all slack users
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

use App\Utility\SlackUtility;

 /**
  * Class CacheSlackUsersCommand
  *
  * @package App\Console\Commands
  */
class CacheSlackUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:slack-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache all the slack users details';

    protected $slackUtility;

    /**
     * CacheSlackUsersCommand constructor.
     *
     * @param SlackUtility $slackUtility SlackUtility for API calls
     */
    public function __construct(SlackUtility $slackUtility)
    {
        parent::__construct();

        $this->slackUtility = $slackUtility;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $slackUsers = $this->slackUtility->getAllUsers();
            $transformedUsers = $this->transform($slackUsers);

            $response = Redis::set("slack:allUsers", json_encode($transformedUsers));

            if ($response->getPayload() !== "OK") {
                $this->error("Slack users not cached.");
            }

            $this->info("Slack users cached successfully.");
        } catch (Exception $e) {
            $this->error("An error occurred, unable to cache slack users.");
        }
    }

    /**
     * Transforms the slack result to a useful model
     *
     * @param array $slackUsers raw result from slack
     *
     * @return array $transformedUsers array of transformed user models
     */
    public function transform($slackUsers)
    {
        $transformedUsers = [];

        foreach ($slackUsers as $user) {
            $transformedUser = new \stdClass();

            $transformedUser->id = $user["id"];
            $transformedUser->fullname = $user["real_name"] ?? "";
            $transformedUser->email = $user["profile"]["email"] ?? "";
            $transformedUser->handle = "@" . $user["name"] ?? "";

            $transformedUsers[$transformedUser->id] = $transformedUser;
        };

        return $transformedUsers;
    }

}
