<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;

/**
 * Class GenerateGoogleCredentials
 *
 * @package App\Console\Commands
 */
class DecodeCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "generate:google-credentials";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Generate google client credentials";

    /**
     * Execute console command
     */
    public function handle()
    {
        try {
            $encoded_credentials = getenv("GOOGLE_SERVICE_KEY");
            if (empty($encoded_credentials)) {
                throw new Exception('Google service key was not provided');
            }

            $write_file = fopen("./credentials.json", "w");
            fwrite($write_file, base64_decode($encoded_credentials));
            fclose($write_file);
        } catch (Exception $e) {
            $this->error('Google service key was not provided');

        }
    }
}
