<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class MailListener
{
    /**
     * Add Lenken tag to all mail headers
     *
     * @param  MessageSending $event Message sending event
     * @return void
     */
    public function handle(MessageSending $event)
    {
        $message = $event->message;

        $headers = $message->getHeaders();

        $headers->addTextHeader("X-Mailgun-Tag", "Lenken");
    }
}
