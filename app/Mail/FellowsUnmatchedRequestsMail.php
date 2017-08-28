<?php
/**
 * File defines class for unmatched requests mail
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
 * Unmatched requests mail for fellows
 * this does not contain sensitive information
 *
 * @category Mailable
 * @package  App\Mail
 */
class FellowsUnmatchedRequestsMail extends Mailable
{
    public $unmatched_requests;

    /**
     * Create a new message instance.
     *
     * @param array $unmatched_requests unmatched requests
     */
    public function __construct($unmatched_requests)
    {
        $this->unmatched_requests = $unmatched_requests;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        foreach ($this->unmatched_requests as $key => $request) {
            $request_skills = array_column($request["request_skills"], "name");
            $this->unmatched_requests[$key]["skills"]
                = implode(", ", $request_skills);
        }

        return $this->view('fellows_unmatched_request');
    }
}
