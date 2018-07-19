<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\UserNotification;

/**
 * Class SendNotificationJob
 *
 * @package App\Jobs
 */
class ValidateAndSendNotificationJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public $payload;
    public $recipient;

    private $notificationTypes = [
        "ConfirmedSessionMail" => "SESSION_NOTIFICATIONS",
        "LoggedSessionMail" => "SESSION_NOTIFICATIONS",
        "UserAcceptanceOrRejectionNotificationMail" => "REQUEST_ACCEPTED_OR_REJECTED",
        "SessionFileNotificationMail" => "FILE_NOTIFICATIONS",
        "MentorIndicatesInterestMail" => "INDICATES_INTEREST",
        "CancelRequestMail" => "WITHDRAWN_INTEREST"
    ];

    public function __construct($recipient, $payload)
    {
        $this->payload = $payload;
        $this->recipient = $recipient;
    }

    /**
    * Gets notification type from the payload
    *
    * @param Object $payload - Email payload
    *
    * @return String $notificationType - Unique notification Id
    */
    private function getNotificationType($payload)
    {
        $emailClass = str_replace("App\Mail\\", "", (string)get_class($payload));

        $notificationType = $this->notificationTypes[$emailClass];

        return $notificationType;
    }

    /**
     * Checks if recipient should recive email
     * notification based on their notification settings
     *
     * @param String $recipient - Receiver of the mail
     * @param $payload - notification payload
     *
     * @return boolean $shouldRecieveEmail - Whether
     * or not a user should recieve the email
     */
    private function validateEmailRecipient($recipient, $payload)
    {
        $notificationType = $this->getNotificationType($payload);

        $shouldRecieveEmail = UserNotification::hasUserAcceptedEmail($recipient, $notificationType);

        return $shouldRecieveEmail;
    }

    /**
     * Handles mail sending
    */
    public function handle()
    {
        $shouldRecieveEmail = $this->validateEmailRecipient($this->recipient, $this->payload);

        if ($shouldRecieveEmail) {
            sendEmailNotification($this->recipient, $this->payload);
        }
    }
}
