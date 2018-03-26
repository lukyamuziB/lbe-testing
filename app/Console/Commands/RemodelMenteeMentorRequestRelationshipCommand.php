<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

 /**
  * Class RemodelMenteeMentorRequestRelationshipCommand
  *
  * @package App\Console\Commands
  */
class RemodelMenteeMentorRequestRelationshipCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "remodel-mentee-mentor-request-relationship";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Remodel the request table and add users_request table";

    /**
     * RemodelMenteeMentorRequestRelationshipCommand constructor.
     *
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
        exec("php artisan dump:request-table");
        $this->info("...dumping the request table to the request.pg file...");

        exec("php artisan migrate");
        $this->info("...remodeling requests table, creating users_request, requests_old, request_type and user_role tables...");

        exec("php artisan restore:request-data");
        $this->info("...Inserting dumped data to requests_old, inserting data into request_users table...");

        exec("composer dumpautoload");

        exec("php artisan db:seed --class=RoleTableSeeder");
        $this->info("...Seeding data to the user_role table...");

        exec("php artisan db:seed --class=RequestTypeTableSeeder");
        $this->info("...Seeding data to the request_type table...");
    }
}
