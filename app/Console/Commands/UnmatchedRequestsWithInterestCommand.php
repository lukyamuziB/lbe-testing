<?php
/**
 * File defines class for a console command to send
 * email notifications to users
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use App\Mail\UnmatchedRequestWithInterestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Exception;
use App\Models\Notification;

use App\Models\Request;
use App\Models\RequestType;
use App\Models\User;
use App\Utility\SlackUtility;
use App\Models\UserNotification;
use App\Clients\AISClient;

/**
 * Class UnmatchedRequestWithInterestCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class UnmatchedRequestsWithInterestCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "notify:unmatched-requests:with-interests";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sends email notification about unmatched requests 
        with interested mentor(s) to the Lenken user who created the request";

    protected $aisClient;

    /**
     * UnmatchedRequestsFellowsCommand constructor.
     *
     * @param SlackUtility $slackUtility
     * @param AISClient    $aisClient AIS client
     */
    public function __construct(
        SlackUtility $slackUtility,
        AISClient $aisClient
    ) {
        parent::__construct();
        $this->slackUtility = $slackUtility;
        $this->aisClient = $aisClient;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $unmatchedRequests = Request::getUnmatchedRequestsWithInterests()
                                    ->toArray();
            $lenkenBaseUrl = getenv("LENKEN_FRONTEND_BASE_URL");
            $count = count($unmatchedRequests);
            
            if (!$unmatchedRequests) {
                $this
                ->error("No unmatched request with interests over 3 days exist");
                return;
            }

            $this->addUserDetailsToRequests($unmatchedRequests);

            foreach ($unmatchedRequests as $request) {
                $userRole = $this->getUserRole($request);
                $user = $request[$userRole];

                $userSetting = UserNotification::getUserSettingById(
                    $user["user_id"],
                    Notification::INDICATES_INTEREST
                );

                $requestTitle = $request["title"];
                $requestUrl = "{$lenkenBaseUrl}/requests/{$request["id"]}";

                if ($userSetting->slack && $user["slack_id"]) {
                    $slackMessage = "Your request with title `{$requestTitle}`".
                        " has mentor(s) who have indicated".
                        " interest in mentoring you".
                        "\n Kindly pick a mentor here: {$requestUrl}";

                    $this->slackUtility->sendMessage(
                        [$user["slack_id"]],
                        $slackMessage
                    );
                }

                if ($userSetting->email) {
                    Mail::to($user["email"])->send(
                        new UnmatchedRequestWithInterestMail(
                            $requestTitle,
                            $requestUrl
                        )
                    );
                }
            }
            $this->info("Notifications have been sent to {$count} user(s)");
        } catch (Exception $e) {
            $this->info($e);
            $this->error("An error occurred - Notifications were not sent");
        }
    }

    /**
     * Get details of users who created the requests
     * from $unmatchedRequests
     *
     * @param array $unmatchedRequests unmatched requests
     *
     * @return void
     */
    public function addUserDetailsToRequests(&$unmatchedRequests)
    {
        foreach ($unmatchedRequests as &$request) {
            $userId = $request["created_by"];
            $user = User::find($userId);
            $userRole = $this->getUserRole($request);
            $request[$userRole] = $user;
        }
    }

    /**
     * Get the role of the user who made the request
     *
     * @param object $request request
     *
     * @return void
     */
    public function getUserRole($request)
    {
        $userRole = $request["request_type_id"] === RequestType::MENTOR_REQUEST  ? "mentee" : "mentor";
        return $userRole;
    }
}
