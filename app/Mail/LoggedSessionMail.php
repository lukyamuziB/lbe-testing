<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Logged Session Mail
 *
 * @category Mailable
 * @package  App\Mail
 */
class LoggedSessionMail extends Mailable
{

    public $payload;
    public $recipient;

    /**
     * Create a new message instance.
     *
     * @param array $payload payload
     * @param string $recipient recipient
     */
    public function __construct($payload, $recipient)
    {
        /**
        * Create a new message instance.
        *
        * @param array $payload payload
        * @param string $recipient recipient
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
            ->view('logged_session_email')
            ->with(
                [
                'payload' => $this->payload
                ]
            );
    }
}
