<?php
/**
 * File defines class for CacheUsersAverageRatingCommand tests
 *
 * PHP version >= 7.0
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

use Symfony\Component\Console\Application;

use App\Console\Commands\CacheUsersAverageRatingCommand;
use App\Models\Rating;
use TestCase;

/**
 * Class TestCacheSlackUsersCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

class TestCacheUsersAverageRatingCommand extends TestCase
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
     * Test caching of users average rating
     */
    public function testHandleSuccessForCachingUsersAverageRating()
    {
        $commandTester = $this->executeCommand(
            $this->application,
            "cache:user-average-rating",
            CacheUsersAverageRatingCommand::class
        );

        $message = "Users average rating cached successfully.\n";

        $this->assertEquals($message, $commandTester->getDisplay());
    }

    /**
     * Test if handle works correctly when there
     * are no new ratings to be cached
     */
    public function testHandleSuccessForNoNewRating()
    {
        $commandTester = $this->executeCommand(
            $this->application,
            "cache:user-average-rating",
            CacheUsersAverageRatingCommand::class
        );

        $commandTester = $this->executeCommand(
            $this->application,
            "cache:user-average-rating",
            CacheUsersAverageRatingCommand::class
        );

        $message = "No average ratings to be cached.\n";

        $this->assertEquals($message, $commandTester->getDisplay());
    }
}
