<?php

namespace App\Http\Controllers;

use App\Exceptions\AccessDeniedException;
use App\Models\Status;
use App\Models\Request as MentorshipRequest;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use RESTActions;

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
            // initialize response object
            $response = ["data"=> []];
            // initialize params object
            $params = [];
            if ($period = $this->getRequestParams($request, "period")) {
                $params["date"] = $period;
            }
            if ($location = $this->getRequestParams($request, "location")) {
                $params["location"] = $location;
            }
            // build all where clauses based off of query params (location & time)
            $mentorship_requests = MentorshipRequest::buildQuery($params)
                ->get();
            $response["data"]["skills_count"] = $this->getSkillCount($mentorship_requests);

            // transform the result objects into API ready responses
            if ($request->input('include')) {
                $includes = explode(",", $request->input('include'));
                if (in_array("totalRequests", $includes)) {
                    $response["data"]["totalRequests"] = MentorshipRequest::buildQuery(
                        $params
                    )->count();
                }
                if (in_array("totalRequestsMatched", $includes)) {
                    $response["data"]["totalRequestsMatched"] = $this->getMatchedRequestsCount($request);
                }
                if (in_array("averageTimeToMatch", $includes)) {
                    $response["data"]["averageTimeToMatch"] = $this->getAverageTimeToMatch($request);
                }
                if (in_array("sessionsCompleted", $includes)) {
                    $response["data"]["sessionsCompleted"] = $this->getSessionsCompletedCount($request);
                }
            }

            return $this->respond(Response::HTTP_OK, $response);
        } catch (AccessDeniedException $exception) {
            return $this->respond(
                Response::HTTP_FORBIDDEN,
                ["message" => $exception->getMessage()]
            );
        }
    }

    /**
     * Gets all matched requests count
     *
     * @param string $request - the request object
     *
     * @return   object
     * @internal param Request $date request payload
     */
    private function getMatchedRequestsCount($request)
    {
        $params = [];
        if ($period = $this->getRequestParams($request, "period")) {
            $params["date"] = $period;
        }
        if ($location = $this->getRequestParams($request, "location")) {
            $params["location"] = $location;
        }
        $params["status"] = array(
            Status::MATCHED
        );

        if ($status = $this->getRequestParams($request, "status")) {
            $params["status"] = explode(",", $status);
        }
        return MentorshipRequest::buildQuery($params)
            ->count();
    }

    /**
     * Calculate the number of occurrences for the skills requested
     *
     * @param object $mentorship_requests object - the request object
     *
     * @return array of skills and the number of occurrences
     */
    private function getSkillCount($mentorship_requests)
    {
        $skill_count = [];

        foreach ($mentorship_requests as $mentorship_request) {
            foreach ($mentorship_request->requestSkills as $skill) {
                array_push($skill_count, $skill->skill->name);
            }
        }

        // format the output
        $skill_count = array_count_values($skill_count);
        $formatted_skill_count = [];

        foreach ($skill_count as $skill_name => $count) {
            $formatted_skill_count[] = ["name" => $skill_name, "count" => $count];
        }

        return $formatted_skill_count;
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

        $params["status"] = array(
            Status::MATCHED
        );

        if ($status = $this->getRequestParams($request, "status")) {
            $params["status"] = explode(",", $status);
        }
        $average_time = MentorshipRequest::buildQuery($params)
            ->groupBy('status_id')
            ->select(
                'status_id',
                DB::raw('(SELECT AVG(match_date - created_at) as average_time)')
            );
        $days = 0;
        if (count($average_time->get()) > 0) {
            /*
            1970-01-01 is added to get only the number of seconds 
            contained in ($average_time->average_time) and
            excluding the number of seconds since 1970-01-01
            */
            $average_time = $average_time->first();
            $average_time_value = $average_time->average_time;
            $timeStamp = strtotime("1970-01-01 ".$average_time_value);
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
        $selected_date = MentorshipRequest::getTimeStamp($request->input('period'));
        $selected_location = MentorshipRequest::getLocation($request->input('location'));

        $sessions_completed = Session::whereHas(
            'request', function ($query) use ($selected_location) {
                if ($selected_location) {
                    $query->where('location', $selected_location);
                }
            }
        )
            ->when(
                $selected_date, function ($query) use ($selected_date) {
                    $query->where('date', '>=', $selected_date);
                }
            )
        ->where('mentee_approved', true)
        ->where('mentor_approved', true)
        ->count();

        return $sessions_completed;
    }

    private function getRequestParams($request, $key)
    {
        return $request->input($key) ?? null;
    }
}
