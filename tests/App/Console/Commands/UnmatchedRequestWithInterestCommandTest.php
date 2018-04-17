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

use App\Mail\UnmatchedRequestWithInterestMail;
use Illuminate\Support\Facades\Mail;
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

        $commandTester = $this->executeCommand(
            $this->application,
            "notify:unmatched-requests:with-interests",
            UnmatchedRequestsWithInterestCommand::class
        );

        Mail::assertSent(UnmatchedRequestWithInterestMail::class, function ($mail) {
            return $mail->hasTo("inumidun.amao@andela.com");
        });

        $message = "Notifications have been sent to 3 user(s)\n";

        $this->assertEquals($commandTester->getDisplay(), $message);
    }
}
