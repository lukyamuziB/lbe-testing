<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class EncryptGoogleCredentialsCommand
 *
 * @package App\Console\Commands
 */
class EncryptGoogleCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encrypt:google-credentials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Encrypt google client credentials";

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
        $this->info($this->encrypt());
    }


    /**
     * Encodes file content using base64
     *
     * @return string base64 encoded string of the credentials.json file
     */
    private function encrypt()
    {
        $file = file_get_contents("credentials.json");

        return base64_encode($file);
    }
}
