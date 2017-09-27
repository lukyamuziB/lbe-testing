<?php
/**
 * File defines class for CacheSlackUsersCommand tests
 *
 * PHP version >= 7.0
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

use Symfony\Component\Console\Application;

use Illuminate\Support\Facades\Redis;
use App\Console\Commands\CacheSlackUsersCommand;
use TestCase;

/**
 * Class TestCacheSlackUsersCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

class TestCacheSlackUsersCommand extends TestCase
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
     * Test caching of slack users
     */
    public function testHandleSuccessForCachinglackUsers()
    {
        $commandTester = $this->executeCommand(
            $this->application,
            "cache:slack-users",
            CacheSlackUsersCommand::class
        );

        $message = "Slack users cached successfully.\n";

        $this->assertEquals($message, $commandTester->getDisplay());
    }
}
