<?php

use TestCase;
use Illuminate\Support\Facades\Mail;
use App\Mail\CancelRequestMail;
use App\Mail\NewRequestMail;
use App\Helpers\SendEmailHelper;
use App\Jobs\SendNotificationJob;

class EmailNotificationTest extends TestCase
{
    public function setUp()
    {
        parent::setup();
    }

    /**
     * Tests if interested users recieve
     * an email notification upon canceling
     * of a request.
     */
    public function testCancelRequestMailSuccess()
    {
        $payload = [
            "currentUser" => "Achola Sam",
            "title" => "JQuery",
            "request_type" => "mentor",
        ];
        $recipientEmail = "test-user-admin@andela.com";

        $emailPayload = new CancelRequestMail($payload, $recipientEmail);
        dispatch(new SendNotificationJob($recipientEmail, $emailPayload));

        Mail::assertSent(CancelRequestMail::class, function ($mail) use ($payload, $recipientEmail) {
            $mail->build();
            return $mail->hasTo($recipientEmail);
        });
    }

    /**
     * Tests if users with open matching mentorship
     * request recieve an email notification
     * of the request
     */
    public function testNewRequestMailSuccess()
    {
        $payload = [
            "currentUser" => "Achola Sam",
            "title" => "Jquery"
        ];
        $recipientEmail = "test-user-admin@andela.com";

        $emailPayload = new NewRequestMail($payload, $recipientEmail);
        dispatch(new SendNotificationJob($recipientEmail, $emailPayload));
        Mail::assertSent(NewRequestMail::class, function ($mail) use ($payload, $recipientEmail) {
            $mail->build();
            return $mail->hasTo($recipientEmail);
        });

    }
}
