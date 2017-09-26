<?php
/**
 * File defines class for EncryptGoogleCredentialsCommand tests
 *
 * PHP version >= 7.0
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

namespace Tests\App\Console\Commands;

use Symfony\Component\Console\Application;

use TestCase;
use App\console\Commands\EncryptGoogleCredentialsCommand;

/**
 * Class TestEncryptGoogleCredentialsCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */

class TestEncryptGoogleCredentialsCommand extends TestCase
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
        $write_file = fopen("credentials.json", "w");
        fwrite($write_file, "This is my string");
        fclose($write_file); 

        $result = $this->executeCommand(
            $this->application,
            "encrypt:google-credentials",
            EncryptGoogleCredentialsCommand::class
        );

        $this->assertEquals("VGhpcyBpcyBteSBzdHJpbmc=\n", $result->getDisplay());

        unlink('credentials.json');
        
    }

    /**
     * Test handle failure
     */
    public function testHandleFailureForMissingCredentialsFile()
    {
        $result = $this->executeCommand(
            $this->application,
            "encrypt:google-credentials",
            EncryptGoogleCredentialsCommand::class
        );

        $this->assertEquals(
            "credentials.json file not found\n\n",
            $result->getDisplay()
        );
    }
}
