<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Confirmed session mail
 *
 * @category Mailable
 * @package  App\Mail
 */
class NewRequestMail extends Mailable
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
            ->view('new_request_email')
            ->with(
                [
                    'payload' => $this->payload
                ]
            );
    }
}
