<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Request;

/**
 * Class UpdateLocationCommand
 *
 * @package App\Console\Commands
 */
class UpdateLocationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "update:location";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "update request location to either Lagos or Nairobi";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $requests = $this->getRequests();

        foreach ($requests as $request) {
            // get where location is WAT and replace it with Lagos
            if ($request->location == "WAT") {
                $request->update(["location" => "Lagos"]);
            }
            // get where location is EAT and replace it with Nairobi
            if ($request->location == "EAT") {
                $request->update(["location" => "Nairobi"]);
            }
        }
    }

    /**
     * This gets all requests where location is
     * stored as WAT or EAT
     *
     * @return array collection of requests
     */
    private function getRequests()
    {
        return Request::whereIn("location", ["WAT", "EAT"])->get();
    }
}
