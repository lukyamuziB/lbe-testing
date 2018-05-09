<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class FailedFreckleLoggingMail extends Mailable
{
    public $sessionDate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($sessionDate)
    {
        $this->sessionDate = $sessionDate;
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
