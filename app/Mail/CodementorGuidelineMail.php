<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Sends an email to placed fellows with unmatched requests
 * within 24 hours.
 *
 * @category Mailable
 * @package  App\Mail
 */
class CodementorGuidelineMail extends Mailable
{
    /**
     * Codementor guideline email constructor
     */
    public function __construct()
    {
        $this->mail_attachment = getenv("CODEMENTOR_ATTACHMENT");
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('codementor_guidelines_email')
            ->attach($this->mail_attachment);
    }
}
