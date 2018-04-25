<?php

namespace App\Http\Controllers\V2;

use App\Models\User;
use App\Models\Skill;
use App\Models\Rating;
use App\Models\Session;
use App\Models\UserSkill;
use App\Clients\AISClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Repositories\SlackUsersRepository;
use App\Models\Request as MentorshipRequest;
use App\Repositories\UsersAverageRatingRepository;

/**
 * Class UserController
 *
 * @package App\Http\Controllers\V2
 */
class UserController extends Controller
{
    use RESTActions;
    private $aisClient;
    private $slackUsersRepository;
    private $usersAverageRatingRepository;

    public function __construct(
        AISClient $aisClient,
        SlackUsersRepository $slackUsersRepository,
        UsersAverageRatingRepository $usersAverageRatingRepository
    ) {
        $this->aisClient = $aisClient;
        $this->slackUsersRepository = $slackUsersRepository;
        $this->usersAverageRatingRepository = $usersAverageRatingRepository;
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
        $aisUser = $this->aisClient->getUserById($id);
        $slackUser = $this->slackUsersRepository->getByEmail($aisUser["email"]);

        $lenkenUser = User::find($aisUser["id"]);

        if (!$lenkenUser) {
            $lenkenUser = User::create([
                "id" => $aisUser["id"],
                "email" => $aisUser["email"],
                "slack_id" => $slackUser->id ?? ""
                ]);
        }

        $requestCount = $this->getMenteeRequestCount($id);

        $sessionDetails = Session::getSessionDetails($id);

        $ratingDetails = $this->usersAverageRatingRepository->getById($id);

        $user = $this->formatUserInfo($aisUser);

        $user["skills"] = $lenkenUser->getSkills();
        $user["request_count"] = $requestCount;
        $user["logged_hours"] = $sessionDetails["totalHours"];
        $user["total_sessions"] = $sessionDetails["totalSessions"];
        $user["rating"] = $ratingDetails->average_rating ?? 0;
        $user["average_mentor_rating"] = $ratingDetails->average_mentor_rating ?? 0;
        $user["average_mentee_rating"] = $ratingDetails->average_mentee_rating ?? 0;
        $user["total_ratings"] = $ratingDetails->session_count ?? 0;

        return $this->respond(Response::HTTP_OK, (object)$user);
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
        return MentorshipRequest::where("created_by", $userId)
            ->count();
    }

    /**
     * Gets multiple user information based on their user ids.
     *
     * @param Request $request - request object
     *
     * @return Response object
     */
    public function getUsersByIds(Request $request)
    {
        $commaSeparatedUserIds = $request->input("ids");
        $userIds = explode(",", $commaSeparatedUserIds);
        $users = [];

        foreach ($userIds as $id) {
            $ratingDetails = $this->usersAverageRatingRepository->getById($id);

            $user = $this->getUserInfo($id);
            $user["rating"] = $ratingDetails->average_rating ?? 0;
            $user["totalRatings"] = $ratingDetails->session_count ?? 0;

            $users[] = $user;
        }

        return $this->respond(Response::HTTP_OK, $users);
    }

    /**
     * Query AIS to get user info
     *
     * @param integer $id - id belonging to user
     *
     * @return array $user - object containing user info
     */
    private function getUserInfo($id)
    {
        $userDetails = $this->aisClient->getUserById($id);

        $user = $this->formatUserInfo($userDetails);
        $user["skills"] = $this->getUserSkills($id);

        return $user;
    }

    private function getUserSkills($id)
    {
        $userSkills = UserSkill::with("skill")->where("user_id", $id)->get();

        $skills = [];
        foreach ($userSkills as $skill) {
            $skills[] = (object)[
                "id" => $skill->skill_id,
                "name" => $skill->skill->name
            ];
        }

        return $skills;
    }

    /**
     * Format ais user information
     *
     * @param object $userDetails - user information from ais
     *
     * @return array
     */
    private function formatUserInfo($userDetails)
    {
        $user = [
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
        ];

        return $user;
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
