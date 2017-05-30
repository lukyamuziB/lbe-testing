<?php

namespace App\Http\Controllers;

use App\Status;
use App\Request as MentorshipRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    use RESTActions;

    /**
     * Gets all Mentorship Requests report
     *
     * @param object $request Request
     * @return Response Object
     */
    public function all(Request $request)
    {
      // initialize response object
      $response = [ "data" => [] ];

      // build where clause based off of query params (location & time)
      $where_clause = MentorshipRequest::buildWhereClause($request);
      $mentorship_requests = MentorshipRequest::where($where_clause)
                                              ->orderBy('created_at', 'desc')->get();

      // transform the result objects into API ready responses
      $skill_count = $this->getSkillCount($mentorship_requests);

      $response["data"]["skills"] = $skill_count;

      // evaluate includes
      if ($request->input('includes')) {
        $includes_options = explode(",", $request->input('includes'));

        if (in_array('totalRequests', $includes_options)) {
          $totalRequests = $this->getRequestsCount($where_clause);
          $response["data"]["totalRequests"] = $totalRequests;
        }

        if (in_array('totalRequestsMatched', $includes_options)) {
          $totalRequestsMatched = $this->getMatchedRequestsCount($where_clause);
          $response["data"]["totalRequestsMatched"] = $totalRequestsMatched;
        }
      }

      return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Gets all requests count
     *
     * @param Array $where_clause array of query params (location & period)
     * @return Number count of total requests made
     */
    private function getRequestsCount($where_clause)
    {
      return MentorshipRequest::where($where_clause)->count();
    }

    /**
     * Gets all matched requests count
     *
     * @param Array $where_clause array of query params (location & period)
     * @return Number count of requests matched
     */
    private function getMatchedRequestsCount($where_clause)
    {
        $where_clause[] = ["status_id", "=", Status::MATCHED];
        return MentorshipRequest::where($where_clause)->count();
    }

    /**
    * Calculate the number of occurences for the skills requested
    *
    * @param $request_skill object
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
}
