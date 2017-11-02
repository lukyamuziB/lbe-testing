<?php
namespace Tests\App\Console\Commands;

use Carbon\Carbon;
use Symfony\Component\Console\Application;

use TestCase;
use App\console\Commands\InactiveMentorshipNotificationCommand;
use App\Models\Request;
use App\Models\User;
use App\Models\Session;
use App\Models\RequestSkill;
use App\Models\Rating;
use App\Models\RequestExtension;

/**
 * Class TestInactiveMentorshipNotificationCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */
class TestInactiveMentorshipNotificationCommand extends TestCase
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
        User::where("user_id", "not", 0)->forceDelete();
    }

    /**
     * Test if handle works correctly when there are
     * inactive mentorships
     */
    public function testHandleSuccessForPresenceOfInactiveRequest()
    {
        $command_tester = $this->executeCommand(
            $this->application,
            "notify:inactive-mentorships",
            InactiveMentorshipNotificationCommand::class
        );
        $message = "Inactive notifications have been sent to 3 fellows\n";

        $this->assertEquals($command_tester->getDisplay(), $message);
    }

    /**
     * Test if handle works correctly when there are
     * no inactive mentorships
     */
    public function testHandleFailureForNoInactiveRequests()
    {
        // delete all valid data
        $this->clearTables();
        /*
         * create user
         */
        User::create(
            [
                "user_id" => "-KXGy1MTimjQgFim7u",
                "email" => "daisy.wanjiru@andela.com",
                "slack_id" => "i-am-active"
            ],
            [
                "user_id" => "-KXGy1MTimjQgFim7u",
                "email" => "adebayo.adesanya@andela.com",
                "slack_id" => "i-am-active-too"
            ]
        );
        /*
        * create request for the fellow
        */
        Request::create(
            [
                'mentee_id' => "-KXGy1MTimjQgFim7u",
                'mentor_id' => "-KXGy1MimjQgFim7u",
                'title' => "Javascript",
                'description' => "Learn Javascript",
                'status_id' => 1,
                'created_at' => "2017-09-19 20:55:24",
                'match_date' => date(Carbon::yesterday()),
                'duration' => 2,
                'pairing' => json_encode(
                    [
                        'start_time' => '01:00',
                        'end_time' => '02:00',
                        'days' => ['monday, tuesday, wednesday, thursday, friday'],
                        'timezone' => 'EAT'
                    ]
                ),
                'location' => "Nairobi"
            ]
        );

        // create session for the request
        Session::create(
            [
            'request_id' => 21,
            'date' => Carbon::today(),
            'start_time' => Carbon::now()->addHour(12),
            'end_time' => Carbon::now()->addHour(14),
            'mentee_approved' => true,
            'mentor_approved' => true,
            'mentee_logged_at' => Carbon::today(),
            'mentor_logged_at' => Carbon::now()
            ]
        );

        $command_tester = $this->executeCommand(
            $this->application,
            "notify:inactive-mentorships",
            InactiveMentorshipNotificationCommand::class
        );
        $message = "There are no inactive mentorships\n";

        $this->assertEquals($command_tester->getDisplay(), $message);
    }
}
