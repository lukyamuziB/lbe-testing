<?php
namespace App\Http\Controllers\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Status;
use App\Models\Request as MentorshipRequest;

/**
 * Class RequestController
 *
 * @package App\Http\Controllers
 */
class RequestController extends Controller
{
    use RESTActions;

    /**
     * Gets Mentorship Requests
     *
     * @param Request $request - request  object
     *
     * @return Array $response - A formatted array of Mentorship Requests
     */
    public function getRequestsPool(Request $request)
    {
        // Get all request params
        $params = [];
        $limit = $request->input("limit") ?
            intval($request->input("limit")) : 20;

        $params["user"] = $request->user()->uid;
        if ($requestsType = $this->getRequestParams($request, "category")) {
            $params["category"] = $requestsType;
        }
        if ($locations = $this->getRequestParams($request, "locations")) {
            $params["locations"] = explode(",", $locations);
        }
        if ($lengths = $this->getRequestParams($request, "lengths")) {
            $params["lengths"] = explode(",", $lengths);
        }
        if ($skills = $this->getRequestParams($request, "skills")) {
            $params["skills"] = explode(",", $skills);
        }
        $mentorshipRequests  = MentorshipRequest::buildPoolFilterQuery($params)
            ->orderBy("created_at", "desc")
            ->paginate($limit);
        $response["requests"] = $this->formatRequestData($mentorshipRequests);
        $response["pagination"] = [
            "totalCount" => $mentorshipRequests->total(),
            "pageSize" => $mentorshipRequests->perPage()
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Gets all completed Mentorship Requests belonging to the logged in user
     *
     * @param Request $request - request  object
     *
     * @return Response Object
     */
    public function getUserHistory(Request $request)
    {
        $userId = $request->user()->uid;
        $mentorshipRequests = MentorshipRequest::where("status_id", STATUS::COMPLETED)
            ->where(
                function ($query) use ($userId) {
                    $query->where("mentor_id", $userId)
                        ->orWhere("mentee_id", $userId);
                }
            )
            ->with("session.rating")
            ->with("requestSkills")
            ->get();
        $this->appendRating($mentorshipRequests);
        $formattedRequests = $this->formatRequestData($mentorshipRequests);
        return $this->respond(Response::HTTP_OK, $formattedRequests);
    }

    /**
     * Gets requests that are awaiting user to select mentor or mentee to select user
     * as mentor
     *
     * @param Request $request - request object
     *
     * @return Response Object
     */
    public function getPendingPool(Request $request)
    {
        $userId = $request->user()->uid;

        $allRequestsWithInterestedMentors
            = MentorshipRequest::where("status_id", STATUS::OPEN)->whereNotNull("interested");

        $requestsInterestedIn
            = $this->getRequestsInterestedIn($allRequestsWithInterestedMentors, $userId);

        $userRequestsWithInterestedMentors
            = $allRequestsWithInterestedMentors->where("mentee_id", $userId)->get();

        $response = [
            "requestsWithInterests" => $this->formatRequestData($userRequestsWithInterestedMentors),
            "requestsInterestedIn" => $this->formatRequestData($requestsInterestedIn),
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Get mentorship requests which user has shown interest in
     *
     * @param array  $requestsWithInterested - requests that have interested mentors
     * @param string $userId - id for currently logged in user
     *
     * @return array $requestsInterestedIn - requests user is interested in
     */
    private function getRequestsInterestedIn($requestsWithInterested, $userId)
    {
        $requestsWithInterested = $requestsWithInterested->get();

        $requestsInterestedIn = [];
        foreach ($requestsWithInterested as $request) {
            if (in_array($userId, $request->interested)) {
                $requestsInterestedIn[] = $request;
            }
        }

        return $requestsInterestedIn;
    }

    /**
     * Get's requests that are in progress for the logged in user
     *
     * @param Request $request - the request object
     *
     * @return array $response - the response object, in progress requests
     */
    public function getRequestsInProgress(Request $request)
    {
        $userId = $request->user()->uid;

        $requestsInProgress = MentorshipRequest::where(
            function ($query) use ($userId) {
                $query->where("mentor_id", $userId)
                    ->orWhere("mentee_id", $userId);
            }
        )
            ->where("status_id", STATUS::MATCHED)
            ->orderBy("created_at", "desc")
            ->get();
        $formattedRequestsInProgress = $this->formatRequestData($requestsInProgress);

        return $this->respond(Response::HTTP_OK, $formattedRequestsInProgress);
    }

    /**
     * Calculate and attach the rating of each request Object
     *
     * @param object $mentorshipRequests - mentorship requests
     *
     * @return {void}
     */
    private function appendRating(&$mentorshipRequests)
    {
        foreach ($mentorshipRequests as $request) {
            $sessions = $request->session;
            $ratings = [];
            foreach ($sessions as $session) {
                if ($session->rating) {
                    $ratingValues = json_decode($session->rating->values);
                    $availability = $ratingValues->availability;
                    $usefulness = $ratingValues->usefulness;
                    $reliability = $ratingValues->reliability;
                    $knowledge = $ratingValues->knowledge;
                    $teaching = $ratingValues->teaching;
                    $rating = ($availability+ $usefulness + $reliability + $knowledge + $teaching)/5;
                    $ratings[] = $rating;
                }
            }
            if (count($sessions) > 0) {
                $request->rating = (array_sum($ratings)/count($sessions));
            } else {
                $request->rating = 0;
            }
        };
    }

    /**
     * Format request data
     * extracts returned request queries to match data on client side
     *
     * @param Object $requests - collection of requests
     *
     * @return array - array of formatted requests
     */
    private function formatRequestData($requests)
    {
        $formattedRequests = [];
        foreach ($requests as $request) {
            $formattedRequest = (object) [
                "id" => $request->id,
                "mentee_id" => $request->mentee_id,
                "mentor_id" => $request->mentor_id,
                "title" => $request->title,
                "description" => $request->description,
                "status_id" => $request->status_id,
                "interested" => $request->interested,
                "match_date" => $request->match_date,
                "location" => $request->location,
                "duration" => $request->duration,
                "pairing" => $request->pairing,
                "request_skills" => $this->formatRequestSkills($request->requestSkills),
                "rating" => $request->rating ?? null,
                "created_at" => $this->formatTime($request->created_at),
            ];
            $formattedRequests[] = $formattedRequest;
        }
        return $formattedRequests;
    }

    /**
     * Format time
     * checks if the given time is null and
     * returns null else it returns the time in the date format
     *
     * @param string $time - the time in the date format
     *
     * @return mixed null|string
     */
    private function formatTime($time)
    {
        return $time === null ? null : date("Y-m-d H:i:s", $time->getTimestamp());
    }

    /**
     * Format Request Skills
     * Filter the result from skills table and add to the skills array
     *
     * @param array $requestSkills - the request skills
     *
     * @return array $skills
     */
    private function formatRequestSkills($requestSkills)
    {
        $skills = [];
        foreach ($requestSkills as $skill) {
            $result = (object) [
                "id" => $skill->skill_id,
                "type" => $skill->type,
                "name" => $skill->skill->name
            ];
            $skills[] = $result;
        }
        return $skills;
    }
}
