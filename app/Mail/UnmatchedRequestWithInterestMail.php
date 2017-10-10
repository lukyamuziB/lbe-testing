<?php
/**
 * File defines class for Unmatched Request With Interest Mail
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
 * Class UnmatchedRequestWithInterestMail
 *
 * @category Mailable
 * @package  App\Mail
 */
class UnmatchedRequestWithInterestMail extends Mailable
{
    public $requestTitle;
    public $requestUrl;
    
     /**
      * UnmatchedRequestWithInterestMail constructor.
      * Create a new message instance
      *
      * @param string $requestTitle the title of the request
      * @param string $requestUrl the request url
      */
    public function __construct($requestTitle, $requestUrl)
    {
        $this->requestTitle = $requestTitle;
        $this->requestUrl = $requestUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view("unmatched_request_with_interest");
    }
}
