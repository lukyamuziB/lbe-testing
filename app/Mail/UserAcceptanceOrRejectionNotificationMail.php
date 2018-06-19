<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\UserNotification;

/**
 * interested user acceptance or rejection mail
 *
 * @category Mailable
 * @package  App\Mail
 */
class UserAcceptanceOrRejectionNotificationMail extends Mailable
{

    private $payload;
    private $recipient;

    /**
     * Create a new message instance.
     *
     * @param string $recipient recipient
     * @param array  $payload payload
     *
     */
    public function __construct($recipient, $payload)
    {
        /**
         * Create a new message instance.
         *
         * @param array $payload
         * @param string $recipient
         *
         */

        $this->payload = $payload;
        $this->recipient = $recipient;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to($this->recipient)
            ->subject($this->payload["emailSubject"])
            ->view("user_acceptance_or_rejection_email")
            ->with(
                [
                "payload" => $this->payload
                ]
            );
    }
}
