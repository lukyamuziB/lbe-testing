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
use App\console\Commands\UpdateCompletedRequestStatusCommand;
use App\Models\Request;
use App\Models\User;
use App\Models\Session;
use App\Models\RequestSkill;
use App\Models\Rating;
use App\Models\RequestExtension;

/**
 * Class TestUpdateFulfilledRequestStatusCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */
class TestUpdateFulfilledRequestStatusCommand extends TestCase
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
     * Test if fulfilled requests are updated
     */
    public function testSuccessUpdateFulfilledRequestStatusCommand()
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
            'created_by' => "-KXGy1MTimjQgFim7u",
            'request_type_id' => 2,
            'title' => "Javascript",
            'description' => "Learn Javascript",
            'status_id' => 2,
            'created_at' => "2014-09-19 20:55:24",
            'match_date' => "2014-09-19 20:55:24",
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
            "update:requests:completed",
            UpdateCompletedRequestStatusCommand::class
        );

        $message =  "1 completed Mentorship Request(s) were updated successfully.\n";

        $this->assertEquals($commandTester->getDisplay(), $message);
    }
}
