<?php

namespace App\Console\Commands;

use App\Mail\ExternalMentorshipGuidelinesMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Exception;

use App\Clients\AISClient as AISClient;
use App\Models\Request as MentorshipRequest;
use App\Mail\SuccessUnmatchedRequestsMail;

class UnmatchedRequestsSuccessCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'notify:unmatched-requests:success';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends an email notification when mentorship 
    requests by placed fellows do not get matched within 24 hours';

    protected $ais_client;
    protected $base_url;

    /**
     * UnmatchedRequestsSuccessCommand constructor.
     *
     * @param AISClient $ais_client AIS client
     */
    public function __construct(AISClient $ais_client)
    {
        parent::__construct();

        $this->ais_client = $ais_client;
        $this->base_url = getenv('LENKEN_FRONTEND_BASE_URL');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            // get all unmatched requests
            $unmatchedRequests = MentorshipRequest::getUnmatchedRequests(24)->get()->toArray();

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

            $appEnvironment = getenv('APP_ENV');

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

            $this->info("External engagement notification sent to placed fellows");
        } catch (Exception $e) {
            $this->error("An error occurred - notifications were not sent");
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


        $response = $this->ais_client->getUsersByEmail($emails);

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
     * Returns an array of unique mentee email addresses from requests
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
                        "placement" => $fellow["placement"],
                        "client" => $fellow["client"],
                        "email" => $fellow["email"],
                        "avatar" => $fellow["avatar"],
                        "request_title" => $request["title"],
                        "request_url"
                        => $this->base_url . '/requests/' . $request["id"],
                        "request_skills"
                        => array_column($request["request_skills"], "skill")
                    ];
                }
            }
        }

        return $menteeRequestData;
    }
}
