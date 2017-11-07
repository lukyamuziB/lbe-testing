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
     * Calculate and attach the rating of each request Object
     * @param  object $mentorshipRequests
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
     * @param object $requests - the result object
     *
     * @return object
     */
    private function formatRequestData($requests)
    {
        $formattedRequests = [];
        foreach ($requests as $request) {
            $formattedRequest = (object) [
                "mentee_id" => $request->mentee_id,
                "mentor_id" => $request->mentor_id,
                "title" => $request->title,
                "status_id" => $request->status_id,
                "match_date" => $request->match_date,
                "created_at" => $this->formatTime($request->created_at),
                "duration" => $request->duration,
                "request_skills" => $this->formatRequestSkills($request->requestSkills),
                "rating" => $request->rating,
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
     * @param  string $time
     * @return mixed null|string
     */
    private function formatTime($time)
    {
        return $time === null ? null : date('Y-m-d H:i:s', $time->getTimestamp());
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
