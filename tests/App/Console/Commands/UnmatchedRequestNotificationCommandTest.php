<?php
/**
 * File defines class for TestUnmatchedRequestsNotificationCommand tests
 *
 * PHP version >= 7.0
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

namespace Tests\App\Console\Commands;

use TestCase;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodementorGuidelineMail;
use App\console\Commands\UnmatchedRequestNotificationCommand;
use Symfony\Component\Console\Application;

use App\Models\Request;
use App\Models\User;
use App\Models\Session;
use App\Models\RequestSkill;
use App\Models\Rating;
use App\Models\RequestExtension;

class TestUnmatchedRequestsNotificationCommand extends TestCase
{
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
    public function testHandleEmailForUnmatchedPlacedFellowRequests()
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
                 'request_type_id' => 1,
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
            "notify:unmatched-fellow-requests",
            UnmatchedRequestNotificationCommand::class
        );
         $message = "There are no unmatched requests for placed fellows\n";

         $this->assertEquals($command_tester->getDisplay(), $message);
    }

}
