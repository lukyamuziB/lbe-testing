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
use App\console\Commands\DecodeCredentialsCommand;

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
     * Test handle success for decode firebase credential
     */
    public function testHandleSuccessForFirebaseCredentials()
    {
        $serviceKey = getenv("FIREBASE_SERVICE_ACCOUNT_KEY=");
        if (empty(trim($serviceKey))) {
            putenv("FIREBASE_SERVICE_ACCOUNT_KEY=ThisIsARandomServiceKeyForTestingOnly");
        }
        
        $this->assertFalse(file_exists("./firebase-credentials.json"));
        
        $command_tester = $this->executeCommand(
            $this->application,
            "credentials:decode",
            DecodeCredentialsCommand::class
        );
        $this->assertTrue(file_exists("./firebase-credentials.json"));

        unlink("./credentials.json");
        unlink("./firebase-credentials.json");
    }

    /**
     * Test handle success for decode google credentials
     */
    public function testHandleSuccessForGoogleCredentials()
    {
        $serviceKey = getenv("GOOGLE_SERVICE_KEY");
        if (empty(trim($serviceKey))) {
            putenv("GOOGLE_SERVICE_KEY=ThisIsARandomServiceKeyForTestingOnly");
        }
        
        $this->assertFalse(file_exists("./credentials.json"));
        
        $command_test = $this->executeCommand(
            $this->application,
            "credentials:decode",
            DecodeCredentialsCommand::class
        );
        $this->assertTrue(file_exists("./credentials.json"));
    }

    /**
     * Test handle failure for missing google service key
     */
    public function testHandleFailureForMissingCredentials()
    {
        putenv("GOOGLE_SERVICE_KEY=");

        $command_tester = $this->executeCommand(
            $this->application,
            "credentials:decode",
            DecodeCredentialsCommand::class
        );

        $this->assertEquals(
            "One or more credential keys not provided in environment.\n",
            $command_tester->getDisplay()
        );
    }
}
