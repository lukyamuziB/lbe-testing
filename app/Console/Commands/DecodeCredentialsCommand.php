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
    protected $signature = "credentials:decode";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Generate client credentials";

    /**
     * Execute console command
     */
    public function handle()
    {
        try {
            $googleServiceAccountKey = getenv("GOOGLE_SERVICE_KEY");
            $firebaseServiceAccountKey = getenv("FIREBASE_SERVICE_ACCOUNT_KEY");

            if (empty($googleServiceAccountKey) || empty($firebaseServiceAccountKey)) {
                throw new Exception("One or more credential keys not provided in environment.");
            }

            $filesToBeGenerated = [
                "credentials.json" => $googleServiceAccountKey,
                "firebase-credentials.json" => $firebaseServiceAccountKey
            ];

            foreach ($filesToBeGenerated as $key => $value) {
                $this->generateFile($key, $value);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Generates a file from the base64 encoded string
     *
     * @param $name - name of the file to be generated
     * @param $content - base64 encoded string representation
     */
    private function generateFile($name, $content)
    {
        $write_file = fopen("./$name", "w");
        fwrite($write_file, base64_decode($content));
        fclose($write_file);
    }
}
