<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Indicates interest request mail
 *
 * @category Mailable
 * @package  App\Mail
 */
class MentorIndicatesInterestMail extends Mailable
{

    public $payload;
    public $recipient;

    /**
     * Create a new message instance.
     *
     * @param array $payload payload
     * @param string $recipient recipient
     *
     */
    public function __construct($payload, $recipient)
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
            ->view("mentor_indicates_interest")
            ->with(
                [
                    'payload' => $this->payload
                ]
            );
    }
}
