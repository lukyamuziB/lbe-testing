<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Session File upload or delete mail
 *
 * @category Mailable
 * @package  App\Mail
 */
class SessionFileNotificationMail extends Mailable
{

    private $payload;
    private $recipient;

    /**
     * Create a new message instance.
     *
     * @param string $recipient recipient
     * @param array $payload payload
     *
     */
    public function __construct($recipient, $payload)
    {
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
            ->view("session_file_notification_email")
            ->with(
                [
                    'payload' => $this->payload
                ]
            );
    }
}
