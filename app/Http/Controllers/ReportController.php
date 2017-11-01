<?php

namespace App\Http\Controllers;

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
     * Gets all Mentorship Requests by location and period
     *
     * @param Request $request - the request object
     *
     * @return   Response object
     * @internal param object $Request
     */
    public function all(Request $request)
    {
        try {
            if ($request->user()->role !== 'Admin') {
                throw new AccessDeniedException('you do not have permission to perform this action');
            }
            // initialize params object
            $params = [];
            
            if ($period = $this->getRequestParams($request, "period")) {
                $params["date"] = $period;
            }
            
            if ($location = $this->getRequestParams($request, "location")) {
                $params["location"] = $location;
            }
            
            $mentorshipRequests = MentorshipRequest::buildQuery($params)
                ->get();

            // Build the response object
            $response["skillsCount"] = $this->getSkillCount($mentorshipRequests);
            $response["totalRequests"] = MentorshipRequest::buildQuery(
                $params
            )->count();

            // Get request counts for all statuses
            $requestsCount = $this->getAllStatusRequestsCounts($mentorshipRequests);

            $response["totalMatchedRequests"] = $requestsCount["matched"];
            $response["totalCompletedRequests"] = $requestsCount["completed"];
            $response["totalOpenRequests"] = $requestsCount["open"];
            $response["totalCancelledRequests"] = $requestsCount["cancelled"];
            $response["averageTimeToMatch"] = $this->getAverageTimeToMatch($request);
            $response["sessionsCompleted"] = $this->getSessionsCompletedCount($request);

            return $this->respond(Response::HTTP_OK, $response);
        } catch (AccessDeniedException $exception) {
            return $this->respond(
                Response::HTTP_FORBIDDEN,
                ["message" => $exception->getMessage()]
            );
        }
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
        if ($request->user()->role !== "Admin") {
            throw new AccessDeniedException("You do not have permission to perform this action");
        }

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
     * Calculate the number of occurrences for the skills requested
     *
     * @param object $mentorshipRequests object - the request object
     *
     * @return array of skills and the number of occurrences
     */
    private function getSkillCount($mentorshipRequests)
    {
        $countSkillsByStatus = [];

        foreach ($mentorshipRequests as $mentorshipRequest) {
            $status = $mentorshipRequest->status->name;
            foreach ($mentorshipRequest->requestSkills as $skill) {
                $countSkillsByStatus[$skill->skill->name][] = $status;
            }
        }

        $skillsCount = [];
        foreach ($countSkillsByStatus as $skill => $statusArray) {
            $countMatch = array_count_values($statusArray);
            $skillsCount[] = ["name" => $skill, "count" => $countMatch];
        }
        return $skillsCount;
    }

    /**
     * Gets the average time to match a request based on the selected filter
     *
     * @param object $request - request payload
     *
     * @return mixed number|null
     */
    private function getAverageTimeToMatch($request)
    {
        $params = [];
        
        if ($period = $this->getRequestParams($request, "period")) {
            $params["date"] = $period;
        }
        
        if ($location = $this->getRequestParams($request, "location")) {
            $params["location"] = $location;
        }
        $params["status"] = array(Status::MATCHED);

        if ($status = $this->getRequestParams($request, "status")) {
            $params["status"] = explode(",", $status);
        }
        
        $averageTime = MentorshipRequest::buildQuery($params)
            ->groupBy('status_id')
            ->select(
                'status_id',
                DB::raw('(SELECT AVG(match_date - created_at) as average_time)')
            );
        $days = 0;
        if (count($averageTime->get()) > 0) {
            /*
            1970-01-01 is added to get only the number of seconds 
            contained in ($averageTime->average_time) and
            excluding the number of seconds since 1970-01-01
            */
            $averageTime = $averageTime->first();
            $averageTimeValue = $averageTime->average_time;
            $timeStamp = strtotime("1970-01-01 ".$averageTimeValue);
            $days = round(($timeStamp/86400));
        }
        
        return (!$days) ? 0 : $days . "day(s)";
    }

    /**
     * Gets count of all sessions completed
     *
     * @param object $request - request payload
     *
     * @return number count of sessions completed
     */
    private function getSessionsCompletedCount($request)
    {
        $selectedDate = MentorshipRequest::getTimeStamp($request->input('period'));
        $selectedLocation = MentorshipRequest::getLocation($request->input('location'));

        $sessionsCompleted = Session::whereHas(
            'request',
            function ($query) use ($selectedLocation) {
                if ($selectedLocation) {
                    $query->where('location', $selectedLocation);
                }
            }
        )->when(
            $selectedDate,
            function ($query) use ($selectedDate) {
                $query->where('date', '>=', $selectedDate);
            }
        )
        ->where('mentee_approved', true)
        ->where('mentor_approved', true)
        ->count();

        return $sessionsCompleted;
    }

    /**
     * Creates an object that contains statuses as the keys
     * and the values initialized to 0. Iterates through all
     * requests and increases the count of corresponding keys
     *
     * @param object $mentorshipRequests - all requests
     *
     * @return object - statuses and corresponding counts
     */
    private function getAllStatusRequestsCounts($mentorshipRequests)
    {
        $requestStatusCount['open'] = 0;
        $requestStatusCount['completed'] = 0;
        $requestStatusCount['cancelled'] = 0;
        $requestStatusCount['matched'] = 0;
 
        foreach ($mentorshipRequests as $mentorshipRequest) {
            if ($mentorshipRequest->status_id == Status::OPEN) {
                $requestStatusCount['open']++;
            }
            if ($mentorshipRequest->status_id == Status::COMPLETED) {
                $requestStatusCount['completed']++;
            }
            if ($mentorshipRequest->status_id == Status::CANCELLED) {
                $requestStatusCount['cancelled']++;
            }
            if ($mentorshipRequest->status_id == Status::MATCHED) {
                $requestStatusCount['matched']++;
            }
        }
        return $requestStatusCount;
    }
    
    /**
     * Gets unmatched requests
     *
     * @param Request $request - the HTTP Request object
     *
     * @return report of unmatched request
     */
    public function getUnmatchedRequests(Request $request)
    {
        $params = [];
        if ($location = $this->getRequestParams($request, "location")) {
            $params["location"] = $location;
        }
        $duration  = $request->input("duration") ?? 0;
        $limit = $request->input("limit") ?? 20;

        $unmatchedRequests = MentorshipRequest::getUnmatchedRequests($duration, $params)->paginate($limit);

        $menteeEmails =  $unmatchedRequests->pluck("mentee.email")->toArray();
        $mentees = $this->aisClient->getUsersByEmail(array_unique($menteeEmails));

        $unmatchedRequestsWithMenteeDetails = [];
        if ($unmatchedRequests) {
            foreach ($unmatchedRequests as $unmatchedRequest) {
                foreach ($mentees["values"] as $mentee) {
                    if ($mentee["email"] === $unmatchedRequest->mentee->email) {
                        $unmatchedRequestsWithMenteeDetails[] = (object)[
                            "id" => $unmatchedRequest->id,
                            "title" => $unmatchedRequest->title,
                            "skills" => array_column($unmatchedRequest->requestSkills->toArray(), "skill"),
                            "createdAt" =>$unmatchedRequest->created_at,
                            "mentee" => (object)[
                                "name" => $mentee["first_name"]. " " . $mentee["last_name"],
                                "avatar" => $mentee["picture"],
                                "client" => $mentee["client"] ?? "Not placed",
                            ]
                        ];
                    }
                }
            }
        }

        $response = [
            "requests" => $unmatchedRequestsWithMenteeDetails,
            "pagination" => [
                "totalCount" => $unmatchedRequests->total(),
                "pageSize" => $unmatchedRequests->perPage()]
        ];
        return $this->respond(Response::HTTP_OK, $response);
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
