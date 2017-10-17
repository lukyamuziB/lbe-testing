<?php
/**
 * File defines class for a console command to send
 * email notifications to users
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Exception;
use App\Models\Request;
use App\Models\Status;

/**
 * Class UpdateFulfilledRequestStatus
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class UpdateCompletedRequestStatusCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "update:requests:completed";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Update fulfilled requests status to completed";

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        try {
            $requests = Request::where("status_id", Status::MATCHED)->get();

            $completedRequestIds = [];
            foreach ($requests as $request) {
                $matchDate = new Carbon($request->match_date);
                $currentDate = Carbon::today();
                $endDate = $matchDate
                                ->addDays($request->duration * 31);

                if ($currentDate->gte($endDate)) {
                    $completedRequestIds[] = $request->id;
                }
            }

            $count = Request::whereIn("id", $completedRequestIds)
                ->update(["status_id" => Status::COMPLETED]);

            $this->info("{$count} completed Mentorship Request(s) were updated successfully.");
        } catch (Exception $e) {
            $this->error(
                "Error occurred while updating Mentorship Request(s)."
            );
        }
    }
}
