<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class FailedFreckleLoggingMail extends Mailable
{
    public $failed_session_details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($failed_session_details)
    {
        $this->failed_session_details = $failed_session_details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('failed_freckle_logging');
    }
}
