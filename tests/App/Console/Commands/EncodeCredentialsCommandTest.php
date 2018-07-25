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
use App\console\Commands\EncodeCredentialsCommand;

/**
 * Class TestEncodeCredentialsCommand
 *
 * @category Tests
 * @package  Tests\App\Console\Commands
 */
class TestEncodeCredentialsCommand extends TestCase
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
        $credentialFiles = explode("\n", file_get_contents("credentials-list.txt"));

        foreach ($credentialFiles as $credentialFile) {
            $writeFile = fopen($credentialFile, "w");
            fwrite($writeFile, "This is my string");
            fclose($writeFile);
        }

        $result = $this->executeCommand(
            $this->application,
            "credentials:encode",
            EncodeCredentialsCommand::class
        );

        $this->assertEquals(
           "credentials.json: VGhpcyBpcyBteSBzdHJpbmc=\nfirebase-credentials.json: VGhpcyBpcyBteSBzdHJpbmc=\n\n",
            $result->getDisplay()
        );

        foreach ($credentialFiles as $credentialFile) {
            unlink($credentialFile);
        }
    }

    /**
     * Test handle failure
     */
    public function testHandleFailureForMissingCredentialsFile()
    {
        unlink("credentials-list.txt");

        $result = $this->executeCommand(
            $this->application,
            "credentials:encode",
            EncodeCredentialsCommand::class
        );

        $this->assertEquals(
            "Cannot find credentials-list.txt file.\n",
            $result->getDisplay()
        );
    }
}
