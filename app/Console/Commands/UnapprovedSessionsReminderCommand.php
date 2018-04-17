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
use App\Models\RequestUsers;
use App\Models\Role;
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

    protected $slackUtility;
    protected $slackRepository;
    protected $aisClient;

    /**
     * UnapprovedSessionsReminderCommand constructor.
     *
     * @param SlackUtility         $slackUtility    SlackUtility for API calls
     * @param SlackUsersRepository $slackRepository slack users repository
     * @param AISClient            $aisClient       AISClient for API calls
     */
    public function __construct(
        SlackUtility $slackUtility,
        SlackUsersRepository $slackRepository,
        AISClient $aisClient
    ) {
        parent::__construct();

        $this->slackUtility = $slackUtility;
        $this->slackRepository = $slackRepository;
        $this->aisClient = $aisClient;
    }

    /**
     * Execute console command
     */
    public function handle()
    {
        try {
            $unapprovedSessions = Session::getUnApprovedSessions(2)
                ->toArray();

            $this->appendMenteeAndMentorDetailsToRequest($unapprovedSessions);

            if ($unapprovedSessions) {
                $sessionDetails = $this->groupSessionsByRecipient($unapprovedSessions);

                $emails = $this->getUserEmails($sessionDetails);

                // get details of people who logged the sessions
                $users = $this->aisClient->getUsersByEmail($emails);

                $this->appendUserDetails($sessionDetails, $users["values"]);

                $userIds = array_keys($sessionDetails);
                // get settings of message recipients
                // (those who have not logged their sessions)
                $settings = UserNotification::getUsersSettingById(
                    $userIds,
                    Notification::LOG_SESSIONS_REMINDER
                );

                $this->appendUserSettings($sessionDetails, $settings);

                $lenkenBaseUrl = getenv("LENKEN_FRONTEND_BASE_URL");

                // send notifications to users
                foreach ($sessionDetails as $recipient) {
                    $user = $this->getRecipient(current($recipient["requests"])[0]);

                    $slackId = $user["slack_id"] ?? "";
                    $email = $user["email"];

                    if ($recipient["notification"]["slack"] && $slackId) {
                        $slackMessage = $this->buildSlackMessage(
                            $recipient["requests"],
                            $lenkenBaseUrl
                        );

                        $this->slackUtility->sendMessage(
                            [$slackId],
                            $slackMessage["title"],
                            json_encode($slackMessage["attachments"])
                        );
                    }

                    // Send email message as well if email setting is true
                    if ($recipient["notification"]["email"]) {
                        $sessionInfo = [];

                        foreach ($recipient["requests"] as $requestId => $request) {
                            $requestUrl = "$lenkenBaseUrl/requests/$requestId";
                            $sessionLogger
                                = $this->getLogger($request[0]);

                            $sessionInfo[] = [
                                "title" => $request[0]["request"]["title"],
                                "sessions_count" => count($request),
                                "url" => $requestUrl,
                                "name" => $sessionLogger["name"],
                                "avatar"
                                => $sessionLogger["avatar"]
                            ];
                        }

                        Mail::to($email)->send(
                            new UnapprovedSessionsMail($sessionInfo)
                        );
                    }
                }
                $numberOfRecipients = count($sessionDetails);
                $this->info(
                    "Notifications have been sent to $numberOfRecipients recipients"
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
     * @param array $unapprovedSessions unapproved sessions
     *
     * @return array unique emails
     */
    private function getUserEmails($unapprovedSessions)
    {
        $emails = [];
        foreach ($unapprovedSessions as $recipient) {
            foreach ($recipient["requests"] as $request) {
                $sessionLogger = $this->getLogger($request[0]);
                $emails[] = $sessionLogger["email"];
            }
        }

        return array_unique($emails);
    }

    /**
     * Append user avatar and name to $sessions
     *
     * @param array $unapprovedSessions unapproved sessions
     * @param array $users               user data from AISClient
     */
    private function appendUserDetails(&$unapprovedSessions, $users)
    {

        $avatars = array_column($users, "picture", "email");
        $names = array_column($users, "name", "email");

        foreach ($unapprovedSessions as $recipientId => $recipient) {
            foreach ($recipient["requests"] as $requestId => $request) {
                $userKey = $request[0]["mentee_approved"] ? "mentee" : "mentor";

                $unapprovedSessions[$recipientId]["requests"][$requestId]
                [0]["request"][$userKey]["avatar"]
                    = $avatars[$request[0]["request"][$userKey]["email"]];
                $unapprovedSessions[$recipientId]["requests"][$requestId]
                [0]["request"][$userKey]["name"]
                    = $names[$request[0]["request"][$userKey]["email"]];
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
        $userIds = array_keys($sessions);
        $slackSettings = array_column($settings, "slack", "user_id");
        $email_settings = array_column($settings, "email", "user_id");

        foreach ($userIds as $userId) {
            $sessions[$userId]["notification"]["slack"]
                = $slackSettings[$userId];
            $sessions[$userId]["notification"]["email"]
                = $email_settings[$userId];
        }
    }

    /**
     * Group sessions based on message recipient's user_id
     *
     * @param array $unapprovedSessions unapproved sessions
     *
     * @return array
     */
    private function groupSessionsByRecipient($unapprovedSessions)
    {
        $sessions = [];

        foreach ($unapprovedSessions as $session) {
            $recipient = $this->getRecipient($session);

            $recipientId = $recipient["id"];

            $sessions[$recipientId]["requests"][$session["request_id"]][]
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
        $userKey = $session["mentee_approved"] ? "mentor" : "mentee";

        return $session["request"][$userKey];
    }

    /**
     * Build each request as an attachment to send in slack message
     * This helps format the request for better display to users
     * This is necessitated by lack of html code support in slack API
     *
     * @param array $sessions        the unapproved sessions
     * @param array $lenkenBaseUrl lenken url
     *
     * @return array
     */
    public function buildSlackMessage($sessions, $lenkenBaseUrl)
    {
        $attachments = [];
        $limit = 0;

        foreach ($sessions as $requestId => $session) {
            $requestUrl = "$lenkenBaseUrl/requests/$requestId";

            $attachments[]
                =  [
                "fallback"
                => "Attachment could not be displayed on your browser",
                "mrkdwn_in" => ["pretext"],
                "pretext" => "```Mentorship Request Number $requestId.```",
                "color" => "#50CFF3",
                "fields" => [
                    [
                        "title" => "Unapproved Sessions",
                        "value" => count($session),
                        "short" => true
                    ],
                    [
                        "title" => "Request Url",
                        "value" => $requestUrl,
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
        $userKey = $session["mentee_approved"] ? "mentee" : "mentor";

        return $session["request"][$userKey];
    }

    /**
     * Append mentee and mentor details to requests
     * from $unapprovedSessions
     *
     * @param array $unapprovedSessions unapproved sessions
     *
     * @return void
     */
    public function appendMenteeAndMentorDetailsToRequest(&$unapprovedSessions)
    {
        foreach ($unapprovedSessions as &$unapprovedSession) {
            $requestId = $unapprovedSession["request"]["id"];
            $mentee = RequestUsers::with("user")
                                        ->where("request_id", $requestId)
                                        ->where("role_id", Role::MENTEE)
                                        ->first()["user"];

            $mentor = RequestUsers::with("user")
                                        ->where("request_id", $requestId)
                                        ->where("role_id", Role::MENTOR)
                                        ->first()["user"];

            $unapprovedSession["request"]["mentee"] = $mentee;
            $unapprovedSession["request"]["mentor"] = $mentor;
        }
    }
}
