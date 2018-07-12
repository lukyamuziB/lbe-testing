<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Unmatched requests mail object sent to placed fellows. This
 * contains guidelines on how they can apply for external mentorships
 *
 * @category Mailable
 * @package  App\Mail
 */
class ExternalMentorshipGuidelinesMail extends Mailable
{
    private $sender;

    /**
     * Create a new message instance.
     *
     * @param array $sender sender
     */
    public function __construct($sender)
    {
        $this->sender = $sender;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from($this->sender)
            ->view('external_mentorship_guidelines');
    }
}
