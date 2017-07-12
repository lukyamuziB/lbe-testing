<?php

namespace App\Console\Commands;

use App\Utility\SlackUtility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

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
     * Create a new command instance.
     *
     * @param  SlackUtility $slackUtility
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
        $slack_users = $this->slackUtility->getAllUsers();

        $transformed_users = $this->transform($slack_users);

        Redis::set("slack:allUsers", json_encode($transformed_users));
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
        $transformed_users = [];

        foreach ($slackUsers as $user) {
            $transformed_user = new \stdClass();

            $transformed_user->id = $user["id"];
            $transformed_user->fullname = $user["real_name"] ?? "";
            $transformed_user->email = $user["profile"]["email"] ?? "";
            $transformed_user->handle = "@" . $user["name"] ?? "";

            $transformed_users[$transformed_user->id] = $transformed_user;
        };

        return $transformed_users;
    }
}
