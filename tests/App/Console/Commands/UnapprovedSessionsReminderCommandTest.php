<?php
/**
 * File defines class for UnapprovedSessionsReminderCommand tests
 *
 * PHP version >= 7.0
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

namespace Tests\App\Console\Commands;

use App\Mail\UnapprovedSessionsMail;
use App\Models\RequestExtension;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Application;

use TestCase;
use App\console\Commands\UnapprovedSessionsReminderCommand;
use App\Models\Request;
use App\Models\User;
use App\Models\Session;
use App\Models\RequestSkill;
use App\Models\Rating;

/**
 * Class TestUnapprovedSessionsReminderCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */
class TestUnapprovedSessionsReminderCommand extends TestCase
{
    private $application;

    /**
     * Setup test dependencies
     */
    public function setUp()
    {
        parent::setUp();

        $this->application = new Application();
    }

    /**
     * Delete data from tables
     */
    private function clearTables()
    {
        User::where("id", "not", 0)->forceDelete();
        RequestSkill::where("id", ">", 0)->forceDelete();
        Rating::where("session_id", ">", 0)->forceDelete();
        Session::where("id", ">", 0)->forceDelete();
        RequestExtension::where("request_id", ">", 0)->forceDelete();
        Request::where("id", ">", 0)->forceDelete();
    }

    /**
     * Test if handle works correctly when there are
     * unapproved sessions
     */
    public function testHandleSuccessForUnapprovedSessions()
    {
        $commandTester = $this->executeCommand(
            $this->application,
            "notify:unapproved-sessions",
            UnapprovedSessionsReminderCommand::class
        );

        Mail::assertSent(
            UnapprovedSessionsMail::class, function ($mail) {
                return $mail->hasTo("inumidun.amao@andela.com");
            }
        );

        $message = "Notifications have been sent to 1 recipients\n";

        $this->assertEquals($commandTester ->getDisplay(), $message);
    }

    /**
     * Test if handle works correctly when there
     * are no unapproved sessions
     */
    public function testHandleSuccessForNoUnapprovedSessions()
    {
        // delete all sessions
        $this->clearTables();

        $commandTester = $this->executeCommand(
            $this->application,
            "notify:unapproved-sessions",
            UnapprovedSessionsReminderCommand::class
        );

        $message = "There are no unapproved sessions\n";

        $this->assertEquals($commandTester ->getDisplay(), $message);
    }

    /**
     * Test handle failure given invalid data
     */
    public function testHandleFailureForInvalidData()
    {
        // delete all valid data
        $this->clearTables();

        /*
         * create user with invalid user_id, email and slack_id
         * that would cause command execution to fail because
         * the user cannot be found
         */
        User::create(
            [
                "id" => "fake_id",
                "email" => "fake.email@andela.com",
                "slack_id" => "fake_slack_id"
            ]
        );

        /*
         * create request with invalid created_by that
         * would cause command execution to fail because the
         * created_by id cannot be found
         */
        $request = Request::create(
            [
                'created_by' => "fake_id",
                'request_type_id' => 2,
                'title' => "Javascript",
                'description' => "Learn Javascript",
                'status_id' => 2,
                'match_date' => null,
                'duration' => 2,
                'pairing' => json_encode(
                    [
                        'start_time' => '01:00',
                        'end_time' => '02:00',
                        'days' => ['monday'],
                        'timezone' => 'EAT'
                    ]
                ),
                'location' => "Nairobi"
            ]
        );

        // create a session that belongs to the above request
        Session::create(
            [
                "request_id" => $request->id,
                "date" => "2017-08-17 00:00:00",
                "start_time"=> "2017-08-18 01:45:14",
                "end_time" => "2017-08-18 03:45:14",
                "mentee_approved" => true,
                "mentor_approved" => null,
                "mentee_logged_at" => "2017-08-17 13:45:14",
                "mentor_logged_at" => null
            ]
        );

        $commandTester = $this->executeCommand(
            $this->application,
            "notify:unapproved-sessions",
            UnapprovedSessionsReminderCommand::class
        );

        $this->assertEquals(
            "An error occurred - notifications were not sent\n",
            $commandTester ->getDisplay()
        );
    }
}
