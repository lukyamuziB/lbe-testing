<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Status;

 /**
  * Class DumpDatabaseCommand
  *
  * @package App\Console\Commands
  */
class RestoreRequestDataFromBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "restore:request-data";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Restore request data from backup and insert in request_users table";

    /**
     * RestoreRequestDataFromBackupCommand constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {

        $host = getenv("DB_HOST");
        $database = getenv("DB_DATABASE");
        $username = getenv("DB_USERNAME");
        $password = getenv("DB_PASSWORD");

        $requestsBackup = file_get_contents("requests.pg");

        $requestsBackup = str_replace("requests", "requests_old", $requestsBackup);

        file_put_contents("requests.pg", $requestsBackup);

        exec("export PGPASSWORD=$password");
        exec("psql --host=$host --username=$username --dbname=$database < requests.pg");

        $allRequestsFromTheBackup = DB::table("requests_old")->get();


        foreach ($allRequestsFromTheBackup as $request) {
            DB::table("request_users")->insert(
                [
                    "user_id" => $request->mentee_id,
                    "role_id" => Role::MENTEE,
                    "request_id" => $request->id
                ]
            );
            if ($request->status_id == Status::MATCHED || $request->status_id == Status::COMPLETED) {
                DB::table("request_users")->insert(
                    [
                        "user_id" => $request->mentor_id,
                        "role_id" => Role::MENTEE,
                        "request_id" => $request->id
                    ]
                );
            }
        }

        $this->info("Request data restored successfully");
    }
}
