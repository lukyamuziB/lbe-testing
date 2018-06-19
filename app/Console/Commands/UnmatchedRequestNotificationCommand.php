<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Clients\AISClient as AISClient;
use App\Models\Status;
use App\Models\User;
use App\Models\RequestType;
use App\Models\Request as MentorshipRequest;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodementorGuidelineMail;

class UnmatchedRequestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var String
     */
    protected $signature = "notify:unmatched-fellow-requests";

    /**
     * The console command description
     *
     * @var String
     */
    protected $description = "Sends an email to placed fellows with unmatched requests within 24 hours";

    protected $aisClient;

    /**
     * Unmatched Request Notification Command
     *
     * @param AISClient $aisClient AIS Client
     *
     */
    public function __construct(AISClient $aisClient)
    {
        parent::__construct();
        $this->aisClient = $aisClient;
    }

    /**
     * Execute command console
     *
     * @return mixed
     */

    public function handle()
    {
        try {
            // Get all unmatched request within the past 24hrs
            $params["mentorshipRequest"] = RequestType::MENTOR_REQUEST;
            $unmatchedRequests = MentorshipRequest::getUnmatchedRequests(24, $params)->get()->toArray();

            if (empty($unmatchedRequests)) {
                return $this->info("There are no unmatched requests");
            }

            // get unmatched requests emails
            $unmatchedRequestEmails = $this->getRequestEmails($unmatchedRequests);

            // Get placed fellow info
            $placedFellows = $this->getPlacedFellows($unmatchedRequestEmails);
            if (empty($placedFellows)) {
                return $this->info("There are no unmatched requests for placed fellows");
            }

            $this->sendCodementorGuidelineEmail($placedFellows);
        } catch (Exception $e) {
            $this->error("An error occured, email was not sent");
        }
    }

     /**
      * Get mentorship request emails.
      * @param array $unmatchedRequests - List of unmatched requests
      * @return array - List of emails
      */
    private function getRequestEmails($unmatchedRequests)
    {
        $emails = [];
        foreach ($unmatchedRequests as $request) {
             $emails[] = $request["created_by"]["email"];
        };

        return array_unique($emails);
    }

    /**
     * Get placed fellows
     *
     * @param $emails - Fellow emails
     * @return array - List of placed fellows
     */
    private function getPlacedFellows($emails)
    {
        $placedFellows = [];
        $placedStatus = ["External Engagements - Standard", "External Engagements - Awaiting Onboarding"];

        $fellows = $this->aisClient->getUsersByEmail($emails);
        foreach ($fellows["values"] as $fellow) {
            if ($fellow["placement"]["status"] !== null && in_array($fellow["placement"]["status"], $placedStatus)) {
                $placedFellows[$fellow["email"]] = [
                    "placement" => $fellow["placement"]["status"],
                    "email" => $fellow["email"],
                    "name" => $fellow["name"]
                ];
            }
        }

        return $placedFellows;
    }

    /**
     * Sends codementor guideline email
     *
     * @param array $recipients - List of recipients.
     */
    private function sendCodementorGuidelineEmail($recipients)
    {
        foreach ($recipients as $recipient) {
            Mail::to($recipient["email"])->send(
                new CodementorGuidelineMail()
            );
        }
    }
}
