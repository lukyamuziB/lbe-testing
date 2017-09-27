<?php
/**
 * File defines class for GenerateGoogleCredentials tests
 *
 * PHP version >= 7.0
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

namespace Tests\App\Console\Commands;

use Symfony\Component\Console\Application;

use TestCase;
use App\console\Commands\GenerateGoogleCredentials;

/**
 * Class TestGenerateGoogleCredentialsCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

class TestGenerateGoogleCredentialsCommand extends TestCase
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
     * Test handle success
     */
    public function testHandleSuccess()
    {
        $serviceKey = getenv("GOOGLE_SERVICE_KEY");
        if (empty(trim($serviceKey))) {
            putenv("GOOGLE_SERVICE_KEY=ThisIsARandomServiceKeyForTestingOnly");
        }
        
        $this->assertFalse(file_exists("./credentials.json"));
        
        $this->executeCommand(
            $this->application,
            "generate:google-credentials",
            GenerateGoogleCredentials::class
        );
        $this->assertTrue(file_exists("./credentials.json"));

        unlink("./credentials.json");
    }

    /**
     * Test handle failure for missing google service key
     */
    public function testHandleFailureForMissingGoogleServiceKey()
    {
        putenv("GOOGLE_SERVICE_KEY=");

        $command_tester = $this->executeCommand(
            $this->application,
            "generate:google-credentials",
            GenerateGoogleCredentials::class
        );

        $this->assertEquals(
            "Google service key was not provided\n",
            $command_tester->getDisplay()
        );
    }
}
