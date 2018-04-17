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

use App\Mail\FellowsUnmatchedRequestsMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Exception;

use App\Models\Request;
use App\Models\RequestType;
use App\Models\User;
use App\Clients\AISClient;

/**
 * Class UnmatchedRequestsFellowsCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class UnmatchedRequestsFellowsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "notify:unmatched-requests:fellows";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sends email notification about 
    unmatched requests to Lenken users";

    protected $baseUrl;
    protected $aisClient;

    /**
     * UnmatchedRequestsFellowsCommand constructor.
     *
     * @param AISClient $aisClient AIS client
     */
    public function __construct(AISClient $aisClient)
    {
        parent::__construct();

        $this->baseUrl = getenv("LENKEN_FRONTEND_baseUrl");
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
            $unmatchedRequests = Request::getUnmatchedRequests()->get()->toArray();

            $this->addUserDetailsToRequests($unmatchedRequests);

            if ($unmatchedRequests) {
                $emailsOfUsersWhoMadeTheRequests = $this->getUsersEmails($unmatchedRequests);

                $usersWhoMadeTheRequests = $this->aisClient
                    ->getUsersByEmail($emailsOfUsersWhoMadeTheRequests);

                // Build the $requests array that will
                // contain only the info we need to send to the template
                $requests = $this->getRequestAndUserDetails(
                    $unmatchedRequests,
                    $usersWhoMadeTheRequests["values"]
                );

                $this->sortRequestsByPlacementStatus($requests);

                $appEnvironment = getenv("APP_ENV");

                $recipients = Config::get(
                    "notifications.{$appEnvironment}.all_fellows_notification_email"
                );

                Mail::to($recipients)->send(
                    new FellowsUnmatchedRequestsMail($requests)
                );
            }

            $count = count($unmatchedRequests);

            if ($count) {
                $message = "A notification about $count unmatched" .
                    " requests has been sent to all fellows";
            } else {
                $message = "There are no unmatched requests";
            }
            $this->info($message);
        } catch (Exception $e) {
            $this->error("An error occurred - emails were not sent");
        }
    }

    /**
     * Get emails of users who created the requests
     * from $unmatchedRequests
     *
     * @param array $unmatchedRequests unmatched requests
     *
     * @return array
     */
    public function getUsersEmails($unmatchedRequests)
    {
        $usersEmails = [];
        foreach ($unmatchedRequests as $request) {
            $userRole = $this->getUserRole($request);
            $usersEmails[] = $request[$userRole]["email"];
        }

        return $usersEmails;
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

    /**
     * Take only the info we need from $unmatchedRequests
     * and $mentees and store it in a new array
     *
     * @param array $unmatchedRequests unmatched requests
     * @param array $users             mentees details
     *
     * @return array
     */
    public function getRequestAndUserDetails($unmatchedRequests, $users)
    {
        $requests = [];
        foreach ($unmatchedRequests as $request) {
            $userRole = $this->getUserRole($request);
            foreach ($users as $user) {
                if ($request[$userRole]["email"] === $user["email"]) {
                    $requests[$request["id"]]
                        = [
                        "client" => $user["placement"]["client"] ?? "",
                        "request_url"
                        => $this->baseUrl . "/requests/" . $request["id"],
                        "request_skills"
                        => array_column($request["request_skills"], "skill")
                        ];
                    break;
                }
            }
        }

        return $requests;
    }

    /**
     * This sorts the array to ensure all placed fellows requests come up first
     *
     * @param array $requests unmatched requests
     *
     * @return void
     */
    public function sortRequestsByPlacementStatus(&$requests)
    {
        $placedFellowRequests= [];
        $unplacedFellowRequests = [];

        foreach ($requests as $request) {
            if ($request["client"] && trim($request["client"]) !== "") {
                $placedFellowRequests[] = $request;
            } else {
                $request["client"] = "Not Placed";
                $unplacedFellowRequests[] = $request;
            }
        }

        $requests = array_merge($placedFellowRequests, $unplacedFellowRequests);
    }
}
