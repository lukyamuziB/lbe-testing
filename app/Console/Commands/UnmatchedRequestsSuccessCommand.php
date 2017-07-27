<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

use App\Clients\AISClient as AISClient;
use App\Models\Request as MentorshipRequest;
use App\Mail\UnmatchedRequestsMail;

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
        // get all unmatched requests
        $unmatched_requests = MentorshipRequest::getUnmatchedRequests(24)->toArray();

        // get all unique emails from the unmatched requests
        $unmatched_requests_emails = $this->getUniqueEmails($unmatched_requests);

        // get placement info from AIS for placed fellows only
        $placed_mentee_info
            = $this->getPlacedMenteeInfoByEmails($unmatched_requests_emails);

        if (empty($placed_mentee_info)) {
            return; //no placed mentees
        }

        // append unmatched request details to mentee's details
        $unmatched_requests_details
            = $this->appendPlacementInfo($unmatched_requests, $placed_mentee_info);

        $app_environment = getenv('APP_ENV');

        $recipient = Config::get(
            "notifications.{$app_environment}.placed_unmatched_fellows_notification_email"
        );

        // send the email
        Mail::to($recipient)->send(
            new UnmatchedRequestsMail($unmatched_requests_details)
        );
    }

    /**
     * This finds the details of mentees(fellows) who are
     * placed on client engagements from AIS
     *
     * @param array $emails emails of fellows/mentees
     *
     * @return array $placed_mentee_info information about placed mentee
     */
    private function getPlacedMenteeInfoByEmails($emails)
    {
        $placed_mentee_info = [];

        $response = $this->ais_client->getUsersByEmail($emails);

        foreach ($response["values"] as $info) {
            if ($info["placement"]) {
                $placed_mentee_info[$info["email"]] = [
                    "placement" => $info["placement"]["status"],
                    "client" => $info["placement"]["client"],
                    "email" => $info["email"],
                    "name" => $info["name"],
                    "avatar" => $info["picture"]
                ];
            }
        }

        return $placed_mentee_info;
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
            $emails[] = $request["user"]["email"];
        }

        return array_unique($emails);
    }

    /**
     * Append information about a fellow's placement to each unmatched request
     *
     * @param array $unmatched_requests unmatched mentorship requests
     * @param array $placed_mentee_info information about feach fellow's placement
     *
     * @return array $mentee_request_data unmatched requests with placement info
     */
    private function appendPlacementInfo($unmatched_requests, $placed_mentee_info)
    {
        $mentee_request_data = [];

        foreach ($placed_mentee_info as $fellow) {
            foreach ($unmatched_requests as $request) {
                if ($request["user"]["email"] === $fellow["email"]) {
                    $mentee_request_data[$request["id"]] = [
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

        return $mentee_request_data;
    }
}
