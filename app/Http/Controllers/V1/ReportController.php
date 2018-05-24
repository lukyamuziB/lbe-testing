<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\AccessDeniedException;
use App\Exceptions\BadRequestException;
use App\Models\Status;
use App\Models\Request as MentorshipRequest;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Clients\AISClient;
use Carbon\Carbon;

class ReportController extends Controller
{
    use RESTActions;
    protected $aisClient;

    /**
     * ReportController constructor.
     *
     * @param AISClient $aisClient AIS client
     */
    public function __construct(AISClient $aisClient)
    {
        $this->aisClient = $aisClient;
    }

    /**
     * Gets the count of inactive sessions for a week
     *
     * @param Request $request - request payload
     *
     * @throws AccessDeniedException|BadRequestException
     *
     * @return object of start and end dates for weeks and inactive session counts
     */
    public function getInactiveMentorshipsReport(Request $request)
    {
        $startDate = $request->input("start_date");
        if (!$startDate) {
            throw new BadRequestException("Start date is required to get report.");
        }
        $startDate = Carbon::createFromFormat("Y-m-d", $startDate);

        $endDate = $request->input("end_date") ?
            Carbon::createFromFormat("Y-m-d", $request->input("end_date")):
            Carbon::today();

        // loop through the given period to get week dates
        $weekDates = [];
        for ($weekDate = $startDate; $weekDate->lte($endDate); $weekDate->addWeek()) {
            $weekDates[] = (object)["startDate" => $weekDate->startOfWeek()->toDateTimeString(),
                                    "endDate" => $weekDate->endOfWeek()->toDateTimeString()];
        }

        $inactiveMentorshipsReport = [];
        foreach ($weekDates as $week) {
            $weeklyCount = $this->getWeeklyInactiveSessionsCount($week->startDate, $week->endDate);
            $inactiveMentorshipsReport[] = (object)["startDate" => $week->startDate, "endDate" => $week->endDate,
                                                    "count" => $weeklyCount];
        }

        return $this->respond(Response::HTTP_OK, $inactiveMentorshipsReport);
    }

    /**
     * Queries database for count of inactive sessions
     *
     * @param string $startDate - start date of the week
     * @param string $endDate - end date of the week
     *
     * @return number of inactive sessions in a given week
     */
    private function getWeeklyInactiveSessionsCount($startDate, $endDate)
    {
        $weeklyInactiveSessionsCount = MentorshipRequest::
            where("status_id", Status::MATCHED)
            ->where("match_date", '<', $startDate)
            ->whereDoesntHave("sessions", function ($query) use ($startDate, $endDate) {
                $query->whereBetween("date", [$startDate, $endDate]);
            })
            ->count();

        return $weeklyInactiveSessionsCount;
    }
}
