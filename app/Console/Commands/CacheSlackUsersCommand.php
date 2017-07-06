<?php

namespace App\Console\Commands;

use App\User;
use App\DripEmailer;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
    protected $client;
    protected $user_info_url;
    protected $token;

    /**
     * Create a new command instance.
     *
     * @param  Client  $client
     * @param  token   $token
     * @param  user_info_url  $user_info_url
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->user_info_url = getenv("SLACK_API_URL")."users.list";
        $this->token = getenv("SLACK_TOKEN");
        $this->client = new Client();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // make request to slack api
        $data = $this->slackRequest();
        // cache the returned response from slack api
        $this->cacheSlackDetails($data);
    }

    /**
     * Cache all users' slack details
     *
     * @param  array  $data 
     * @return array of user details objects
     */
    private function cacheSlackDetails($data)
    {
        return Cache::put('slack-users', $data, 24 * 60);
    }

    private function slackRequest()
    {
        $response = $this->client->request('POST', $this->user_info_url,
            [
                "form_params" => [
                    "token" => $this->token
                ]
            ]
        );
        $response = json_decode($response->getBody(), true);
        return $response;
    }
}
