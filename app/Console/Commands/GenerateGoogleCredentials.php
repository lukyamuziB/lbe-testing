<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class GenerateGoogleCredentials
 *
 * @package App\Console\Commands
 */
class GenerateGoogleCredentials extends Command
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
        $encoded_credentials = getenv("GOOGLE_SERVICE_KEY");

        $write_file = fopen("./credentials.json", "w");
        fwrite($write_file, base64_decode($encoded_credentials));
        fclose($write_file);
    }
}
