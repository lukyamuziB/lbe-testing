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
    * Gets all Mentorship Requests by location and period
    *
    * @param Request object
    * @return Response object
    */
    public function index(Request $request) {
      $where_clause = MentorshipRequest::buildWhereClause($request);
      $mentorship_requests = MentorshipRequest::where($where_clause)
                                              ->orderBy('created_at', 'desc')->get();

      // transform the result objects into API ready responses
      $skill_count = $this->getSkillCount($mentorship_requests);
      $response = [
          'data' => $skill_count
      ];

      return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Gets all Mentorship Requests report
     *
     * @param object $request Request
     * @return Response Object
     */
    public function all(Request $request)
    {
        $requests_count = $this->getRequestsCount($request->input('period'));
        $matched_count = $this->getMatchedRequestsCount($request->input('period'));

        $response = (object) [
            'data' => compact('requests_count', 'matched_count')
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Gets all requests count
     *
     * @param date $date date period expected
     * @return number
     */
    private function getRequestsCount($date)
    {
        $selected_date = MentorshipRequest::getTimeStamp($date);

        return MentorshipRequest::when($selected_date, function($query) use ($selected_date) {
            if ($selected_date) {
                return $query->whereDate('created_at', '>=', $selected_date);
            }
        })
        ->count();
    }

    /**
     * Gets all matched requests count
     *
     * @param date $date date period expected
     * @return number
     */
    private function getMatchedRequestsCount($date)
    {
        $selected_date = MentorshipRequest::getTimeStamp($date);

        return MentorshipRequest::where('status_id', Status::MATCHED)
            ->when($selected_date, function($query) use ($selected_date) {
                if ($selected_date) {
                    return $query->where('created_at', '>=', $selected_date);
                }
            })
            ->count();
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
