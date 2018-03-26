<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;

/**
 * Class DumpRequestTableCommand
 *
 * @package App\Console\Commands
 */
class DumpRequestTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "dump:request-table";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Dump the request table to a backup file";

    /**
     * DumpRequestTableCommand constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function handle()
    {

        $host = getenv("DB_HOST");
        $database = getenv("DB_DATABASE");
        $username = getenv("DB_USERNAME");
        $password = getenv("DB_PASSWORD");

        exec("export PGPASSWORD=$password");

        if (empty(getenv("PGPASSWORD")) || getenv("PGPASSWORD") != $password) {
            throw new Exception("Error with password.");
        }

        exec("pg_dump --host=$host --username=$username --dbname=$database  --data-only --table=requests > requests.pg");

        $this->info("Database dumped successfully");
    }
}
