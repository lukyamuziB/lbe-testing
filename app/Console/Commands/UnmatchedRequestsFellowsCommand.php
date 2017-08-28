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

use App\Models\Request;
use App\Mail\SuccessUnmatchedRequestsMail;
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

    protected $base_url;
    protected $ais_client;

    /**
     * UnmatchedRequestsFellowsCommand constructor.
     *
     * @param AISClient $ais_client AIS client
     */
    public function __construct(AISClient $ais_client)
    {
        parent::__construct();

        $this->base_url = getenv("LENKEN_FRONTEND_BASE_URL");
        $this->ais_client = $ais_client;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $unmatched_requests = Request::getUnmatchedRequests()->toArray();

        if ($unmatched_requests) {
            $mentee_emails = $this->getMenteeEmails($unmatched_requests);

            $mentees = $this->ais_client
                ->getUsersByEmail($mentee_emails, count($mentee_emails));

            // Build the $requests array that will
            // contain only the info we need to send to the template
            $requests = $this->getRequestAndMenteeDetails(
                $unmatched_requests,
                $mentees["values"]
            );

            $this->sortRequestsByPlacementStatus($requests);

            $app_environment = getenv("APP_ENV");

            $recipients = Config::get(
                "notifications.{$app_environment}.all_fellows_notification_email"
            );

            Mail::to($recipients)->send(
                new FellowsUnmatchedRequestsMail($requests)
            );
        }
    }

    /**
     * Get emails of mentees from $unmatched_requests
     *
     * @param array $unmatched_requests unmatched requests
     *
     * @return array
     */
    public function getMenteeEmails($unmatched_requests)
    {
        $mentee_emails = [];
        foreach ($unmatched_requests as $request) {
            $mentee_emails[] = $request["user"]["email"];
        }

        return $mentee_emails;
    }

    /**
     * Take only the info we need from $unmatched_requests
     * and $mentees and store it in a new array
     *
     * @param array $unmatched_requests unmatched requests
     * @param array $mentees            mentees details
     *
     * @return array
     */
    public function getRequestAndMenteeDetails($unmatched_requests, $mentees)
    {
        $requests = [];
        foreach ($unmatched_requests as $request) {
            foreach ($mentees as $mentee) {
                if ($request["user"]["email"] === $mentee["email"]) {
                    $requests[$request["id"]]
                        = [
                        "client" => $mentee["placement"]["client"],
                        "request_url" => $this->base_url . "/requests/" . $request["id"],
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
     */
    public function sortRequestsByPlacementStatus(&$requests)
    {
        $placed_fellow_requests= [];
        $unplaced_fellow_requests = [];

        foreach ($requests as $request) {
            if ($request["client"] && trim($request["client"]) !== "") {
                $placed_fellow_requests[] = $request;
            } else {
                $request["client"] = "Not Placed";
                $unplaced_fellow_requests[] = $request;
            }
        }

        $requests = array_merge($placed_fellow_requests, $unplaced_fellow_requests);
    }
}
