<?php
/**
 * File defines class for a console command to send
 * slack and email notifications to users
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Exception;

use App\Models\Notification;
use App\Models\Session;
use App\Utility\SlackUtility;
use App\Repositories\SlackUsersRepository;
use App\Models\UserNotification;
use App\Mail\UnapprovedSessionsMail;
use App\Clients\AISClient;

/**
 * Class UnapprovedSessionsReminderCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class UnapprovedSessionsReminderCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "notify:unapproved-sessions";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sends a slack message about unapproved sessions";

    protected $slack_utility;
    protected $slack_repository;
    protected $ais_client;

    /**
     * UnapprovedSessionsReminderCommand constructor.
     *
     * @param SlackUtility         $slack_utility    SlackUtility for API calls
     * @param SlackUsersRepository $slack_repository slack users repository
     * @param AISClient            $ais_client       AISClient for API calls
     */
    public function __construct(
        SlackUtility $slack_utility,
        SlackUsersRepository $slack_repository,
        AISClient $ais_client
    ) {
        parent::__construct();

        $this->slack_utility = $slack_utility;
        $this->slack_repository = $slack_repository;
        $this->ais_client = $ais_client;
    }

    /**
     * Execute console command
     */
    public function handle()
    {
        try {
            $unapproved_sessions = Session::getUnApprovedSessions(2)
                ->toArray();

            if ($unapproved_sessions) {
                $session_details = $this->groupSessionsByRecipient($unapproved_sessions);

                $emails = $this->getUserEmails($session_details);

                // get details of people who logged the sessions
                $users = $this->ais_client->getUsersByEmail($emails);

                $this->appendUserDetails($session_details, $users["values"]);

                $user_ids = array_keys($session_details);

                // get settings of message recipients
                // (those who have not logged their sessions)
                $settings = UserNotification::getUsersSettingById(
                    $user_ids,
                    Notification::LOG_SESSIONS_REMINDER
                );

                $this->appendUserSettings($session_details, $settings);

                $lenken_base_url = getenv("LENKEN_FRONTEND_BASE_URL");

                // send notifications to users
                foreach ($session_details as $recipient) {
                    $user = $this->getRecipient(current($recipient["requests"])[0]);

                    $slack_id = $user["slack_id"] ?? "";
                    $email = $user["email"];

                    if ($recipient["notification"]["slack"] && $slack_id) {
                        $slack_message = $this->buildSlackMessage(
                            $recipient["requests"],
                            $lenken_base_url
                        );

                        $this->slack_utility->sendMessage(
                            [$slack_id],
                            $slack_message["title"],
                            json_encode($slack_message["attachments"])
                        );
                    }

                    // Send email message as well if email setting is true
                    if ($recipient["notification"]["email"]) {
                        $session_info = [];

                        foreach ($recipient["requests"] as $request_id => $request) {
                            $request_url = "$lenken_base_url/requests/$request_id";
                            $session_logger
                                = $this->getLogger($request[0]);

                            $session_info[] = [
                                "title" => $request[0]["request"]["title"],
                                "sessions_count" => count($request),
                                "url" => $request_url,
                                "name" => $session_logger["name"],
                                "avatar"
                                => $session_logger["avatar"]
                            ];
                        }

                        Mail::to($email)->send(
                            new UnapprovedSessionsMail($session_info)
                        );
                    }
                }
                $number_of_recipients = count($session_details);
                $this->info(
                    "Notifications have been sent to $number_of_recipients recipients"
                );
            } else {
                $this->info("There are no unapproved sessions");
            }
        } catch (Exception $e) {
            $this->error("An error occurred - notifications were not sent");
        }
    }

    /**
     * Get emails of people who logged sessions from $sessions
     *
     * @param array $unapproved_sessions unapproved sessions
     *
     * @return array unique emails
     */
    private function getUserEmails($unapproved_sessions)
    {
        $emails = [];
        foreach ($unapproved_sessions as $recipient) {
            foreach ($recipient["requests"] as $request) {
                $session_logger = $this->getLogger($request[0]);
                $emails[] = $session_logger["email"];
            }
        }

        return array_unique($emails);
    }

    /**
     * Append user avatar and name to $sessions
     *
     * @param array $unapproved_sessions unapproved sessions
     * @param array $users               user data from AISClient
     */
    private function appendUserDetails(&$unapproved_sessions, $users)
    {
        $avatars = array_column($users, "picture", "email");
        $names = array_column($users, "name", "email");

        foreach ($unapproved_sessions as $recipient_id => $recipient) {
            foreach ($recipient["requests"] as $request_id => $request) {
                $user_key = $request[0]["mentee_approved"] ? "mentee" : "mentor";

                $unapproved_sessions[$recipient_id]["requests"][$request_id]
                [0]["request"][$user_key]["avatar"]
                    = $avatars[$request[0]["request"][$user_key]["email"]];
                $unapproved_sessions[$recipient_id]["requests"][$request_id]
                [0]["request"][$user_key]["name"]
                    = $names[$request[0]["request"][$user_key]["email"]];
            }
        }
    }

    /**
     * Add user notification settings to unapproved sessions array
     *
     * @param array $sessions unapproved sessions
     * @param array $settings user settings
     */
    private function appendUserSettings(&$sessions, $settings)
    {
        $user_ids = array_keys($sessions);
        $slack_settings = array_column($settings, "slack", "user_id");
        $email_settings = array_column($settings, "email", "user_id");

        foreach ($user_ids as $user_id) {
            $sessions[$user_id]["notification"]["slack"]
                = $slack_settings[$user_id];
            $sessions[$user_id]["notification"]["email"]
                = $email_settings[$user_id];
        }
    }

    /**
     * Group sessions based on message recipient's user_id
     *
     * @param array $unapproved_sessions unapproved sessions
     *
     * @return array
     */
    private function groupSessionsByRecipient($unapproved_sessions)
    {
        $sessions = [];

        foreach ($unapproved_sessions as $session) {
            $recipient = $this->getRecipient($session);

            $recipient_id = $recipient["user_id"];

            $sessions[$recipient_id]["requests"][$session["request_id"]][]
                = $session;
        }

        return $sessions;
    }

    /**
     * Get user (mentee/mentor) who has not logged the session
     *
     * @param array $session session details
     *
     * @return array user key or user details
     */
    private function getRecipient($session)
    {
        $user_key = $session["mentee_approved"] ? "mentor" : "mentee";

        return $session["request"][$user_key];
    }

    /**
     * Build each request as an attachment to send in slack message
     * This helps format the request for better display to users
     * This is necessitated by lack of html code support in slack API
     *
     * @param array $sessions        the unapproved sessions
     * @param array $lenken_base_url lenken url
     *
     * @return array
     */
    public function buildSlackMessage($sessions, $lenken_base_url)
    {
        $attachments = [];
        $limit = 0;

        foreach ($sessions as $request_id => $session) {
            $request_url = "$lenken_base_url/requests/$request_id";

            $attachments[]
                =  [
                "fallback"
                => "Attachment could not be displayed on your browser",
                "mrkdwn_in" => ["pretext"],
                "pretext" => "```Mentorship Request Number $request_id.```",
                "color" => "#50CFF3",
                "fields" => [
                    [
                        "title" => "Unapproved Sessions",
                        "value" => count($session),
                        "short" => true
                    ],
                    [
                        "title" => "Request Url",
                        "value" => $request_url,
                        "short" => true
                    ]
                ]
                ];
            $limit++;

            // it is unlikely that one can have so many requests with unapproved
            // sessions but slack API supports a maximum of 100 attachments
            if ($limit === 100) {
                break;
            }
        }
        $title = "Mentorship request(s) for which you still" .
            " have unapproved sessions. Please review:";

        return ["attachments" => $attachments, "title" => $title];
    }

    /**
     * Get user (mentee/mentor) who has logged the session
     *
     * @param array $session session details
     *
     * @return array logger details
     */
    private function getLogger($session)
    {
        $user_key = $session["mentee_approved"] ? "mentee" : "mentor";

        return $session["request"][$user_key];
    }
}
