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
        "CancelRequestMail" => "REQUEST_CANCELED_OR_REJECTED",
        "ConfirmSessionMail" => "SESSION_NOTIFICATIONS",
        "LoggedSessionMail" => "SESSION_NOTIFICATIONS",
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
        $emailClass = get_class($payload);

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
        $notificationType = getNotificationType($payload);

        $shouldRecieveEmail = UserNotification::hasUserAcceptedEmail($recipient, $notificationType);

        return $shouldRecieveEmail;
    }

    /**
     * Handles mail sending
    */
    public function handle()
    {
        $shouldRecieveEmail = validateEmailRecipient($this->recipient, $this->payload);

        if ($shouldRecieveEmail) {
            sendEmailNotification($this->recipient, $this->payload);
        }
    }
}
