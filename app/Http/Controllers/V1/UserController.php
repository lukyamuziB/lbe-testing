<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Request as MentorshipRequest;
use App\Clients\AISClient;
use App\Models\UserSkill;
use App\Models\Skill;
use App\Models\Session;
use App\Models\Rating;
use App\Models\Status;
use App\Models\RequestSkill;
use App\Repositories\UsersAverageRatingRepository;

class UserController extends Controller
{
    use RESTActions;
    private $aisClient;
    private $usersAverageRatingRepository;

    public function __construct(
        AISClient $aisClient,
        UsersAverageRatingRepository $usersAverageRatingRepository
    ) {
        $this->aisClient = $aisClient;
        $this->usersAverageRatingRepository = $usersAverageRatingRepository;
    }

    /**
     * Gets a user's infomation based on their user id.
     *
     * @return Response object
     */
    public function get(Request $request, $id)
    {
        $userDetails = $this->aisClient->getUserById($id);

        $userSkillObjects = UserSkill::with('skill')->where('user_id', $id)->get();

        $userSkills = [];

        foreach ($userSkillObjects as $skill) {
            $userSkills[$skill->skill->name] = (object)[
                "id" => $skill->skill_id,
                "name" => $skill->skill->name
            ];
        }

        $userSkills = array_values($userSkills);

        $requestCount = $this->getMenteeRequests($id);

        $sessionDetails = Session::getSessionDetails($id);

        $ratingDetails = $this->usersAverageRatingRepository->getById($id);

        $response = (object) [
            "id" => $userDetails["id"],
            "picture" => $userDetails["picture"],
            "first_name" => $userDetails["first_name"],
            "name" => $userDetails["name"],
            "location" => $userDetails["location"]["name"] ?? "",
            "cohort" => $userDetails["cohort"] ?? "",
            "roles" => $userDetails["roles"],
            "placement" => $userDetails["placement"] ?? "",
            "email" => $userDetails["email"],
            "level" => $userDetails["level"] ?? "",
            "skills" => $userSkills,
            "request_count" => $requestCount,
            "logged_hours" => $sessionDetails["totalHours"],
            "total_sessions" => $sessionDetails["totalSessions"],
            "rating" => (object)[
                "cumulative_average" => $ratingDetails->average_rating ?? 0,
                "mentee_average" => $ratingDetails->average_mentee_rating ?? 0,
                "mentor_average" => $ratingDetails->average_mentor_rating ?? 0,
                "rating_count" => $ratingDetails->session_count ?? 0
            ]
        ];

        if ($this->getRequestParams($request, "include") === "skills_gained") {
            $userRequestSkills = $this->getUserGainedSkills($id);

            $userGainedSkills = [];

            foreach ($userRequestSkills as $requestSkill) {
                $userGainedSkills[$requestSkill->skill->name] = (object)[
                    "id" => $requestSkill->skill->id,
                    "name" => $requestSkill->skill->name
                ];
            }

            $userGainedSkills = array_values($userGainedSkills);

            $response->skills_gained = $userGainedSkills;
        }

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Returns the requests for a particular mentee
     *
     * @param string $user_id
     * @return integer total number of mentorship request made
     */
    private function getMenteeRequests($userId)
    {
        return MentorshipRequest::where('created_by', $userId)
            ->count();
    }

     /**
     * Returns all skills from completed requests for a particular user
     *
     * @param string $user_id
     * @return object completed request skills
     */
    public function getUserGainedSkills($userId)
    {
        return RequestSkill::whereIn('request_id', MentorshipRequest::select("id", "title")
            ->where('created_by', $userId)
            ->where("status_id", Status::COMPLETED)
            ->get()->pluck("id"))
            ->get();
    }
}
