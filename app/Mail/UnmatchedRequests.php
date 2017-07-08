<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class UnmatchedRequests extends Mailable
{
    public $unmatched_requests_details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($unmatched_requests_details)
    {
        $this->unmatched_requests_details = $unmatched_requests_details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('unmatched_request');
    }

}
