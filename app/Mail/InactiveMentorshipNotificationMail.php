<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Inactive notification mail sent to mentors and mentees. This
 * email is sent to fellows not logging sessions
 *
 * @category Mailable
 * @package  App\Mail
 */
class InactiveMentorshipNotificationMail extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('inactive_notification_email');
    }
}
