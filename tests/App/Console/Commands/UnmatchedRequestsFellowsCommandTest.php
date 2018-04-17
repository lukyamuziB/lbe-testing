<?php
/**
 * File defines class for UnmatchedRequestsFellowsCommand tests
 *
 * PHP version >= 7.0
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

namespace Tests\App\Console\Commands;

use App\Mail\FellowsUnmatchedRequestsMail;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Application;

use TestCase;
use App\console\Commands\UnmatchedRequestsFellowsCommand;
use App\Models\Request;
use App\Models\User;
use App\Models\RequestSkill;
use App\Models\Session;
use App\Models\Rating;
use App\Models\RequestExtension;

/**
 * Class TestUnmatchedRequestsFellowsCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */
class TestUnmatchedRequestsFellowsCommand extends TestCase
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
     * unmatched requests
     */
    public function testHandleSuccessForUnmatchedRequests()
    {
        $command_tester = $this->executeCommand(
            $this->application,
            "notify:unmatched-requests:fellows",
            UnmatchedRequestsFellowsCommand::class
        );

        Mail::assertSent(FellowsUnmatchedRequestsMail::class, function ($mail) {
            return $mail->hasTo("test-user-admin@andela.com");
        });

        $message = "A notification about 12 unmatched " .
            "requests has been sent to all fellows\n";

        $this->assertEquals($command_tester->getDisplay(), $message);
    }

    /**
     * Test if handle works correctly when there
     * are no unmatched requests
     */
    public function testHandleSuccessForNoUnmatchedRequests()
    {
        // delete all requests
        $this->clearTables();

        $command_tester = $this->executeCommand(
            $this->application,
            "notify:unmatched-requests:fellows",
            UnmatchedRequestsFellowsCommand::class
        );

        $message = "There are no unmatched requests\n";

        $this->assertEquals($command_tester->getDisplay(), $message);
    }

    /**
     * Test handle failure given invalid data
     */
    public function testHandleFailureForInvalidData()
    {
        // delete all valid data
        $this->clearTables();

        // create user with invalid data that can cause handle() failure
        User::create(
            [
                "id" => "fake_id",
                "email" => "fake.email@andela.com",
                "slack_id" => "fake_slack_id"
            ]
        );

        // create request with invalid data that can cause handle() failure
        Request::create(
            [
                'created_by' => "fake_id",
                'request_type_id' => 2,
                'title' => "Javascript",
                'description' => "Learn Javascript",
                'status_id' => 1,
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
            "notify:unmatched-requests:fellows",
            UnmatchedRequestsFellowsCommand::class
        );

        $this->assertEquals(
            "An error occurred - emails were not sent\n",
            $command_tester->getDisplay()
        );
    }
}
