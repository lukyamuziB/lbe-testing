<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class SendNotificationJob
 *
 * @package App\Jobs
 */
class SendNotificationJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public $payload;
    public $recipient;

    public function __construct($recipient, $payload)
    {
        $this->payload = $payload;
        $this->recipient = $recipient;
    }

     /**
     * Handles mail sending
     *
    */
    public function handle()
    {
        Mail::to($this->recipient)->send(
            $this->payload
        );
    }
}
