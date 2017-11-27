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

use Symfony\Component\Console\Application;

use TestCase;
use App\console\Commands\UnmatchedRequestsWithInterestCommand;
use App\Models\Request;
use App\Models\User;
use App\Models\Session;
use App\Models\RequestSkill;
use App\Models\Rating;
use App\Models\RequestExtension;

/**
 * Class TestUnmatchedRequestsInterestCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */
class TestUnmatchedRequestsWithInterestCommand extends TestCase
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
         RequestExtension::where("id", ">", 0)->forceDelete();
         RequestSkill::where("id", ">", 0)->forceDelete();
         Rating::where("session_id", ">", 0)->forceDelete();
         Session::where("id", ">", 0)->forceDelete();
         Request::where("id", ">", 0)->forceDelete();
         User::where("user_id", "not", 0)->forceDelete();
    }
 
    /**
     * Test if command works correctly when they are
     * no unmatched requests with interest
     */
    public function testUnmatchedRequestsWithInterestCommand()
    {
        $this->clearTables();

        $commandTester = $this->executeCommand(
            $this->application,
            "notify:unmatched-requests:with-interests",
            UnmatchedRequestsWithInterestCommand::class
        );

        $message = "No unmatched request with interests over 3 days exist\n";

        $this->assertEquals($commandTester->getDisplay(), $message);
    }

    /**
     * Test if command works correctly when they are unmatched
     * requests with interest
     */
    public function testSuccessUnmatchedRequestsWithInterestCommand()
    {
        $this->clearTables();
    
        /*
        * Create a new user
        *
        */
        User::create(
            [
                "user_id" => "-KXGy1MTimjQgFim7u",
                "email" => "daisy.wanjiru@andela.com",
                "slack_id" => "idgoeshere"
            ]
        );

        /*
         * create request
        */
        Request::create(
            [
                'mentee_id' => "-KXGy1MTimjQgFim7u",
                'title' => "Javascript",
                'description' => "Learn Javascript",
                'status_id' => 1,
                'created_at' => "2017-09-19 20:55:24",
                'match_date' => null,
                'interested' => ["-K_nkl19N6-EGNa0W8LF"],
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
         $commandTester = $this->executeCommand(
             $this->application,
             "notify:unmatched-requests:with-interests",
             UnmatchedRequestsWithInterestCommand::class
         );
         $message = "Notifications have been sent to 1 user(s)\n";
         
        $this->assertEquals($commandTester->getDisplay(), $message);
    }
}
