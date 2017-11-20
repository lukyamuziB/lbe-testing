<?php

namespace App\Http\Controllers\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Clients\AISClient;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\Session;
use App\Models\Rating;
use App\Models\Request as MentorshipRequest;

/**
 * Class UserController
 *
 * @package App\Http\Controllers\V2
 */
class UserController extends Controller
{
    use RESTActions;
    private $aisClient;

    public function __construct(AISClient $aisClient)
    {
        $this->aisClient = $aisClient;
    }

    /**
     * Gets a user's infomation based on their user id.
     *
     * @param integer $id - user id
     *
     * @throws NotFoundException
     *
     * @return object Response object
     */
    public function get($id)
    {
        $user = User::find($id);

        if (!$user) {
            throw new NotFoundException("User not found.");
        }

        $userDetails = $this->aisClient->getUserById($id);

        $userSkills = UserSkill::with("skill")->where("user_id", $id)->get();

        $skills = [];
        foreach ($userSkills as $skill) {
            $skills[] = (object)[
                "id" => $skill->skill_id,
                "name" => $skill->skill->name
            ];
        }

        $skills = array_values($skills);

        $requestCount = $this->getMenteeRequestCount($id);

        $sessionDetails = Session::getSessionDetails($id);

        $ratingDetails = Rating::getRatingDetails($id);

        $response = (object)[
            "id" => $userDetails["id"],
            "picture" => $userDetails["picture"],
            "firstName" => $userDetails["first_name"],
            "name" => $userDetails["name"],
            "location" => $userDetails["location"]["name"] ?? "",
            "cohort" => $userDetails["cohort"] ?? "",
            "roles" => $userDetails["roles"],
            "placement" => $userDetails["placement"] ?? "",
            "email" => $userDetails["email"],
            "level" => $userDetails["level"] ?? "",
            "skills" => $skills,
            "requestCount" => $requestCount,
            "loggedHours" => $sessionDetails["totalHours"],
            "totalSessions" => $sessionDetails["totalSessions"],
            "rating" => $ratingDetails["average_rating"],
            "totalRatings" => $ratingDetails["total_ratings"]
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }
    
    /**
     * Returns the requests for a particular mentee
     *
     * @param string $userId - user id
     *
     * @return integer total number of mentorship request made
     */
    private function getMenteeRequestCount($userId)
    {
        return MentorshipRequest::where("mentee_id", $userId)
            ->count();
    }

    /**
     * Add a user skill
     *
     * @param Request $request - the request object
     * @param integer $userId - user id
     *
     * @throws NotFoundException|ConflictException
     *
     * @return object - Response object
     */
    public function addUserSkill(Request $request, $userId)
    {
        $skillId = $request->input("skill_id");

        if (!Skill::find($skillId)) {
            throw new NotFoundException("Skill does not exist.");
        }

        if (UserSkill::where("skill_id", $skillId)->where("user_id", $userId)->exists()
        ) {
            throw new ConflictException("User skill already exists.");
        }

        UserSkill::create(
            ["skill_id" => $skillId, "user_id" => $userId]
        );

        return $this->respond(Response::HTTP_CREATED, ["message" => "User skill added."]);
    }

    /**
     * Delete a user skill
     *
     * @param integer $userId - user id
     * @param integer $skillId - skill id
     *
     * @throws NotFoundException
     *
     * @return object - Response object
     */
    public function deleteUserSkill($userId, $skillId)
    {
        $skill = UserSkill::where("skill_id", $skillId)->where("user_id", $userId);

        if (!$skill->exists()) {
            throw new NotFoundException("User skill does not exist.");
        }

        $skill->delete();

        return $this->respond(Response::HTTP_OK, ["message" => "User skill deleted."]);
    }
}
