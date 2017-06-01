<?php

namespace App\Http\Controllers;

use App\Status;
use App\Request as MentorshipRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonInterval;

class ReportController extends Controller
{
    use RESTActions;

    /**
     * Gets all Mentorship Requests by location and period
     *
     * @param Request object
     * @return Response object
     */
    public function all(Request $request)
    {
        // initialize response object
        $response = ["data"=> []];

        // build all where clauses based off of query params (location & time)
        $mentorship_requests = MentorshipRequest::buildWhereClause($request)->get();

        $response["data"]["skills_count"] = $this->getSkillCount($mentorship_requests);

        // transform the result objects into API ready responses
        if ($request->input('include')) {
            $includes = explode(",", $request->input('include'));

            if (in_array("totalRequests", $includes)) {
                $response["data"]["totalRequests"] = MentorshipRequest::buildWhereClause($request)->count();
            }

            if (in_array("totalRequestsMatched", $includes)) {
                $response["data"]["totalRequestsMatched"] = $this->getMatchedRequestsCount($request);
            }

            if (in_array("averageTimeToMatch", $includes)) {
                $response["data"]["averageTimeToMatch"] = $this->getAverageTimeToMatch($request);
            }
         }

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Gets all matched requests count
     *
     * @param $date request request payload
     * @return number
     */
    private function getMatchedRequestsCount($request)
    {
        return MentorshipRequest::buildWhereClause($request)
            ->where('status_id', Status::MATCHED)
            ->count();
    }

    /**
     * Calculate the number of occurrences for the skills requested
     *
     * @param $mentorship_requests object
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

        foreach($skill_count as $skill_name => $count) {
            $formatted_skill_count[] = ["name" => $skill_name, "count" => $count];
        }

        return $formatted_skill_count;
    }

    /**
     * Gets the average time to match a request based on the selected filter
     *
     * @param $request request payload
     * @return mixed number|null
     */
    private function getAverageTimeToMatch($request)
    {
        $average_time = MentorshipRequest::buildWhereClause($request)
            ->groupBy('status_id')
            ->having('status_id', Status::MATCHED)
            ->select(
                'status_id',
                DB::raw('(SELECT AVG(match_date - created_at) as average_time)')
            )
            ->first();

        if (!$average_time) {
            return null;
        }

        return $average_time->average_time;
    }
}
