<?php
/**
 * File defines class for unlogged sessions mail
 * object that contains data which is
 * passed to the view before an email is sent
 *
 * PHP version >= 7.0
 *
 * @category Mailable
 * @package  App\Mail
 */

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Class UnapprovedSessionsMail
 *
 * @category Mailable
 * @package  App\Mail
 */
class UnapprovedSessionsMail extends Mailable
{
    public $sessions;

    /**
     * UnapprovedSessionsMail constructor.
     * Create a new message instance
     *
     * @param string $session the message
     */
    public function __construct($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view("unapproved_sessions");
    }
}
