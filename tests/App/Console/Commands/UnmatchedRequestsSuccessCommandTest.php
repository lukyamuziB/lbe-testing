<?php
/**
 * File defines class for TestUnmatchedRequestsSuccessCommand tests
 *
 * PHP version >= 7.0
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

namespace Tests\App\Console\Commands;

use App\Mail\SuccessUnmatchedRequestsMail;
use App\Models\RequestCancellationReason;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Application;

use TestCase;
use App\console\Commands\UnmatchedRequestsSuccessCommand;
use App\Models\Request;
use App\Models\User;
use App\Models\Session;
use App\Models\RequestSkill;
use App\Models\Rating;
use App\Models\RequestExtension;

/**
 * Class TestUnmatchedRequestsSuccessCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */
class TestUnmatchedRequestsSuccessCommand extends TestCase
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
        RequestSkill::where("id", ">", 0)->forceDelete();
        Rating::where("session_id", ">", 0)->forceDelete();
        Session::where("id", ">", 0)->forceDelete();
        RequestExtension::where("request_id", ">", 0)->forceDelete();
        Request::where("id", ">", 0)->forceDelete();
        User::where("id", "not", 0)->forceDelete();
    }

    /**
     * Test if handle works correctly when there are
     * unmatched placed fellow request
     */
    public function testHandleSuccessForUnmatchedPlacedFellowRequests()
    {
        $command_tester = $this->executeCommand(
            $this->application,
            "notify:unmatched-requests:success",
            UnmatchedRequestsSuccessCommand::class
        );

        Mail::assertSent(SuccessUnmatchedRequestsMail::class, function ($mail) {
            return $mail->hasTo("test-user-admin@andela.com");
        });

        $message = "Notifications have been ".
            "sent for 9 placed fellows\nExternal ".
            "engagement notification sent to placed fellows\n";

        $this->assertEquals($command_tester->getDisplay(), $message);
    }

    /**
     * Test if unattended requests are cancelled after 2 successful emails
     */
    public function testCancelUnattendedRequests()
    {
        $commandTester = null;

        /*
         * run command 3 times so request is closed instead of sending
         * third email
         */
        for ($i = 0; $i < 3; $i++) {
            $commandTester = $this->executeCommand(
                $this->application,
                "notify:unmatched-requests:success",
                UnmatchedRequestsSuccessCommand::class
            );
        }

        $message = "9 abandoned request(s) cancelled\n".
            "There are no unmatched requests\n";

        $this->assertEquals($commandTester->getDisplay(), $message);
    }

    /**
     * Test if handle works correctly when there
     * are no unmatched request from placed fellows
     */
    public function testHandleSuccessForNoUnmatchedPlacedFellowRequests()
    {
        // delete all valid data
        $this->clearTables();

        /*
         * create user who is an un-placed fellow
         */
        User::create(
            [
                "id" => "-KXGy1MTimjQgFim7u",
                "email" => "daisy.wanjiru@andela.com",
                "slack_id" => "i-am-not-a-placed-fellow"
            ]
        );

        /*
        * create request for un-placed fellow
        */
        Request::create(
            [
                'created_by' => "-KXGy1MTimjQgFim7u",
                'request_type_id' => 2,
                'title' => "Javascript",
                'description' => "Learn Javascript",
                'status_id' => 1,
                'placement' => ["client" => "Available", "status" =>"Available"],
                'created_at' => "2017-09-19 20:55:24",
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

        $command_tester = $this->executeCommand(
            $this->application,
            "notify:unmatched-requests:success",
            UnmatchedRequestsSuccessCommand::class
        );
        $message = "There are no unmatched requests for placed fellows\n";

        $this->assertEquals($command_tester->getDisplay(), $message);
    }

    /**
     * Test handle failure given invalid user data
     */
    public function testHandleFailureForInvalidData()
    {
        // delete all valid data
        $this->clearTables();

        /*
         * create user with invalid user_id, email and slack_id
         * that would cause command execution to fail because
         * the user was not found
         */
        User::create(
            [
                "id" => "fake_id",
                "email" => "fake.email@andela.com",
                "slack_id" => "fake_slack_id"
            ]
        );

        $command_tester = $this->executeCommand(
            $this->application,
            "notify:unmatched-requests:success",
            UnmatchedRequestsSuccessCommand::class
        );

        $this->assertEquals(
            "There are no unmatched requests\n",
            $command_tester->getDisplay()
        );
    }
}
