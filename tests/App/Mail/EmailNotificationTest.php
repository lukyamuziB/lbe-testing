<?php

use TestCase;
use Illuminate\Support\Facades\Mail;
use App\Mail\CancelRequestMail;
use App\Mail\NewRequestMail;
use\App\Mail\SessionFileNotificationMail;
use App\Mail\UserAcceptanceOrRejectionNotificationMail;
use App\Mail\CodementorGuidelineMail;
use App\Helpers\SendEmailHelper;
use App\Jobs\SendNotificationJob;

use App\console\Commands\UnmatchedRequestNotificationCommand;
use Symfony\Component\Console\Application;

class EmailNotificationTest extends TestCase
{
    public function setUp()
    {
        parent::setup();
        $this->application = new Application();
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

    /**
     * This test handles user notification when either a mentor
     * or a mentee accepts a mentee or mentor who has
     * indicated interest in a request
     *
     */
    public function testAcceptInterestedUserMailSuccess()
    {
        $payload = [
            "fullname" =>  "Michael Briggs",
            "requestedSkill" =>  "Babel",
            "userRole" => "Mentor",
            "notificationType" => "accept-user",
            "emailSubject" => "Interested User Rejection Notification"
        ];
        $recipientEmail = "test-user-admin@andela.com";

        $emailPayload = new UserAcceptanceOrRejectionNotificationMail(
            $recipientEmail,
            $payload
        );
        dispatch(new SendNotificationJob($recipientEmail, $emailPayload));
        Mail::assertSent(
            UserAcceptanceOrRejectionNotificationMail::class,
            function ($mail) use ($recipientEmail, $payload) {
                $mail->build();
                return $mail->hasTo($recipientEmail);
            }
        );

    }

    /**
     * This test handles user notification when either a mentor
     * or a mentee rejects a mentee or mentor who has
     * indicated interest in a request
     *
     */
    public function testRejectInterestedUserMailSuccess()
    {
        $payload = [
            "fullname" =>  "Michael Briggs",
            "requestedSkill" =>  "Babel",
            "userRole" => "Mentor",
            "notificationType" => "reject-user",
            "emailSubject" => "Interested User Rejection Notification"
        ];
        $recipientEmail = "test-user-admin@andela.com";

        $emailPayload = new UserAcceptanceOrRejectionNotificationMail(
            $recipientEmail,
            $payload
        );
        dispatch(new SendNotificationJob($recipientEmail, $emailPayload));
        Mail::assertSent(
            UserAcceptanceOrRejectionNotificationMail::class,
            function ($mail) use ($recipientEmail, $payload) {
                $mail->build();
                return $mail->hasTo($recipientEmail);
            }
        );

    }

    /**
     * Test if email is sent when a mentee or a mentor
     * uploads a session file
     *
     */
    public function testAttachSessionFileNotificationMailSuccess()
    {
        $payload = [
            "fileName" =>  "test-file-name",
            "sessionDate" => "June 18th, 2018",
            "typeOfAction" => "upload_file"
        ];
        $recipientEmail = "test-user-admin@andela.com";

        $emailPayload = new SessionFileNotificationMail(
            $recipientEmail,
            $payload
        );
        dispatch(new SendNotificationJob($recipientEmail, $emailPayload));
        Mail::assertSent(
            SessionFileNotificationMail::class,
            function ($mail) use ($recipientEmail, $payload) {
                $mail->build();
                return $mail->hasTo($recipientEmail);
            }
        );

    }

    /**
     * Test if email is sent when a mentee or a mentor
     * deletes a session file
     *
     */
    public function testDetachSessionFileNotificationMailSuccess()
    {
        $payload = [
            "fileName" =>  "test-file-name",
            "sessionDate" => "June 18th, 2018",
            "typeOfAction" => "delete_file"
        ];
        $recipientEmail = "test-user-admin@andela.com";

        $emailPayload = new SessionFileNotificationMail(
            $recipientEmail,
            $payload
        );
        dispatch(new SendNotificationJob($recipientEmail, $emailPayload));
        Mail::assertSent(
            SessionFileNotificationMail::class,
            function ($mail) use ($recipientEmail, $payload) {
                $mail->build();
                return $mail->hasTo($recipientEmail);
            }
        );
    }

    /**
     * Tests if placed fellows with open  mentorship
     * request recieve an email notification if not
     * matched within 24 hours
     *
     */
    public function testCodementorGuidelineMail()
    {
        $command_tester = $this->executeCommand(
            $this->application,
            "notify:unmatched-fellow-requests",
            UnmatchedRequestNotificationCommand::class
        );
        Mail::assertSent(CodementorGuidelineMail::class);
    }
}
