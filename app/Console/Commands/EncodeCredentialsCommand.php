<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class EncodeCredentialsCommand
 *
 * @package App\Console\Commands
 */
class EncodeCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "credentials:encode";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Encode all listed credential files in the application.";

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!file_exists("credentials-list.txt")) {
            $this->error("Cannot find credentials-list.txt file.");
            return;
        }

        $credentialFiles = explode("\n", file_get_contents("credentials-list.txt"));

        foreach ($credentialFiles as $credentialFile) {
            $this->info("$credentialFile: "   . $this->encode($credentialFile));
        }
    }


    /**
     * Encodes file content using base64
     *
     * @return string base64 encoded string of the credentials.json file
     */
    private function encode($fileName)
    {
        if (file_exists($fileName)) {
            $fileContents = file_get_contents($fileName);
            return base64_encode($fileContents);
        }
    }
}
