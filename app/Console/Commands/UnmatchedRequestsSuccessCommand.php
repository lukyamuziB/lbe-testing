<?php

namespace App\Console\Commands;

use App\Mail\ExternalMentorshipGuidelinesMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Exception;

use App\Clients\AISClient as AISClient;
use App\Models\Status;
use App\Models\User;
use App\Models\RequestType;
use App\Models\Request as MentorshipRequest;
use App\Models\RequestCancellationReason;
use App\Mail\SuccessUnmatchedRequestsMail;

class UnmatchedRequestsSuccessCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "notify:unmatched-requests:success";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sends an email notification when mentorship 
    requests by placed fellows do not get matched within 24 hours";

    protected $aisClient;
    protected $baseUrl;

    /**
     * UnmatchedRequestsSuccessCommand constructor.
     *
     * @param AISClient $aisClient AIS client
     */
    public function __construct(AISClient $aisClient)
    {
        parent::__construct();

        $this->aisClient = $aisClient;
        $this->baseUrl = getenv("LENKEN_FRONTEND_BASE_URL");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            // get requests with more than two emails sent for them
            $abandonedRequests = $this->getAbandonedRequests();

            // find and cancel abandoned requests.
            if ($abandonedRequests) {
                $this->cancelRequests($abandonedRequests);
                $cancelledRequestCount = count($abandonedRequests);
                $this->info("$cancelledRequestCount abandoned request(s) cancelled");
            }

            // get all unmatched requests
            $params["mentorRequest"] = RequestType::MENTOR_REQUEST;

            $unmatchedRequests = MentorshipRequest::getUnmatchedRequests(24, $params)->get()->toArray();

            if (empty($unmatchedRequests)) {
                return $this->info("There are no unmatched requests");
            }

            $this->addUserDetailsToUmnatchedRequests($unmatchedRequests);
            // get all unique emails from the unmatched requests
            $unmatchedRequestsEmails = $this->getUniqueEmails($unmatchedRequests);


            // get placement info from AIS for placed fellows only
            $placedMenteeInfo
                = $this->getPlacedMenteeInfoByEmails($unmatchedRequestsEmails);

            if (empty($placedMenteeInfo)) {
                return $this->info("There are no unmatched requests for placed fellows");
            }

            // append unmatched request details to mentee's details
            $unmatchedRequestsDetails
                = $this->appendPlacementInfo($unmatchedRequests, $placedMenteeInfo);

            $appEnvironment = getenv("APP_ENV");

            $recipient = Config::get(
                "notifications.{$appEnvironment}.placed_unmatched_fellows_notification_email"
            );

            // send the email
            Mail::to($recipient)->send(
                new SuccessUnmatchedRequestsMail($unmatchedRequestsDetails)
            );

            $numberOfRecipients = count($unmatchedRequests);
            $this->info("Notifications have been sent for $numberOfRecipients placed fellows");

            // send notifications to placed fellows that made the requests
            $placedMenteeEmails = array_keys($placedMenteeInfo);
            Mail::to($placedMenteeEmails)->send(
                new ExternalMentorshipGuidelinesMail($recipient)
            );

            // Cache placed fellow mentorship requests email count.
            $this->cacheRequestEmailCount($unmatchedRequestsDetails);
            $this->info("External engagement notification sent to placed fellows");
        } catch (Exception $e) {
            $this->error("An error occurred - notifications were not sent");
        }
    }

    /**
     * Caches the number of times email for abandoned request is sent
     *
     * @param array $unmatchedRequestDetails - Placed mentee details
     * for unmatched requests.
     *
     * @return void
     */
    public function cacheRequestEmailCount($unmatchedRequestDetails)
    {

        $cachedRequests = Cache::get("requests:emailNotificationCount") ?? [];
        $requestToCache = [];

        // Creates emails sent counter and saves to cache.

        foreach ($unmatchedRequestDetails as $requestId => $unmatchedRequest) {
            $requestToCache[$requestId] = [
                "emailCount" => array_key_exists($requestId, $cachedRequests)
                    ? intval($cachedRequests[$requestId]["emailCount"]) + 1 : 1,
                "mentee_id" => $unmatchedRequest["mentee_id"]
            ];
        }

        // Add to cache.
        Cache::forever("requests:emailNotificationCount", $requestToCache);
    }

    /**
     * Gets number of requests with 2 emails sent.
     *
     * @return array $abandonedMentorRequests - request and mentee ids
     */
    public function getAbandonedRequests()
    {
        // Get requests with two emails sent
        $cachedRequests = Cache::get("requests:emailNotificationCount") ?? [];
        $abandonedRequests = [];

        foreach ($cachedRequests as $requestId => $content) {
            if ($content["emailCount"] >= 2) {
                $abandonedRequests[] = [
                    "request_id" => $requestId,
                    "mentee_id" => $content["mentee_id"]
                ];
            }
        }
        return $abandonedRequests;
    }

    /**
     * Cancels requests with the ids provided.
     *
     * @param array $requests - All requests that are not matched.
     *
     * @return void
     */
    public function cancelRequests($requests)
    {

        $requestIdsToCancel = array_column($requests, "request_id");

        // Cancel requests.
        MentorshipRequest::whereIn("id", $requestIdsToCancel)->update(["status_id" => Status::CANCELLED]);

        // Add cancellation reasons
        foreach ($requests as $request) {
            RequestCancellationReason::create(
                [
                    "request_id" => $request["request_id"],
                    "user_id" => $request["mentee_id"],
                    "reason" => "Mentee abandoned"
                ]
            );
        }
    }

    /**
     * This finds the details of mentees(fellows) who are
     * placed on client engagements from AIS
     *
     * @param array $emails emails of fellows/mentees
     *
     * @return array $placedMenteeInfo information about placed mentee
     */
    private function getPlacedMenteeInfoByEmails($emails)
    {
        $placedMenteeInfo = [];

        $response = $this->aisClient->getUsersByEmail($emails);

        $placedStatus = ["External Engagements - Standard", "External Engagements - Awaiting Onboarding"];

        foreach ($response["values"] as $info) {
            if ($info["placement"]["status"] !== null && in_array($info["placement"]["status"], $placedStatus)) {
                $placedMenteeInfo[$info["email"]] = [
                    "placement" => $info["placement"]["status"],
                    "client" => $info["placement"]["client"],
                    "email" => $info["email"],
                    "name" => $info["name"],
                    "avatar" => $info["picture"]
                ];
            }
        }

        return $placedMenteeInfo;
    }

    /**
     * Returns an array of unique user email addresses from requests
     *
     * @param array $requests unmatched requests
     *
     * @return array unmatched_request_emails
     */
    private function getUniqueEmails($requests)
    {
        $emails = [];

        foreach ($requests as $request) {
            $emails[] = $request["mentee"]["email"];
        }

        return array_unique($emails);
    }

    /**
     * Append information about a fellow's placement to each unmatched request
     *
     * @param array $unmatchedRequests unmatched mentorship requests
     * @param array $placedMenteeInfo  information about feach fellow's placement
     *
     * @return array $menteeRequestData unmatched requests with placement info
     */
    private function appendPlacementInfo($unmatchedRequests, $placedMenteeInfo)
    {
        $menteeRequestData = [];

        foreach ($placedMenteeInfo as $fellow) {
            foreach ($unmatchedRequests as $request) {
                if ($request["mentee"]["email"] === $fellow["email"]) {
                    $menteeRequestData[$request["id"]] = [
                        "name" => $fellow["name"],
                        "mentee_id" => $request['mentee']['id'],
                        "placement" => $fellow["placement"],
                        "client" => $fellow["client"],
                        "email" => $fellow["email"],
                        "avatar" => $fellow["avatar"],
                        "request_title" => $request["title"],
                        "request_url"
                        => $this->baseUrl . "/requests/" . $request["id"],
                        "request_skills"
                        => array_column($request["request_skills"], "skill")
                    ];
                }
            }
        }
        return $menteeRequestData;
    }

    /**
     * Get details of users who created the requests
     * from $unmatchedRequests
     *
     * @param array $unmatchedRequests unmatched requests
     *
     * @return void
     */
    public function addUserDetailsToUmnatchedRequests(&$unmatchedRequests)
    {
        foreach ($unmatchedRequests as &$request) {
            $user = $request["created_by"];
            $request["mentee"] = $user;
        }
    }
}
