<?php

namespace App\Http\Controllers\V2;

use App\Models\User;
use App\Models\Skill;
use App\Models\UserSkill;
use App\Clients\AISClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Repositories\SlackUsersRepository;
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
     * Gets a user's information based on their user id.
     *
     * @param integer $userId  Unique id of the user
     * @param Request $request HttpRequest object
     *
     * @throws NotFoundException
     *
     * @return \Illuminate\Http\JsonResponse User details object
     */
    public function get(Request $request, $userId)
    {
        $aisUser = $this->aisClient->getUserById($userId);
        $slackUser = $this->slackUsersRepository->getByEmail($aisUser["email"]);

        $lenkenUser = User::find($aisUser["id"]);

        if (!$lenkenUser) {
            $lenkenUser = User::create(
                [
                "id" => $aisUser["id"],
                "email" => $aisUser["email"],
                "slack_id" => $slackUser->id ?? ""
                ]
            );
        }

        $user = $this->formatUserInfo($aisUser);

        if ($categories = $this->getRequestParams($request, "categories")) {
            $categories= explode(",", $categories);

            $userDetails = $lenkenUser->appendProfileCategoryDetails($categories);

            if (in_array("rating", $categories)) {
                $userDetails["rating"] = $this->getUserRating($userId);
            }

            $user += $userDetails;
        }

        return $this->respond(Response::HTTP_OK, (object)$user);
    }

    /**
     * Get user skills.
     *
     * @param $id  Unique id of the user
     *
     * @throws NotFoundException
     *
     * @return \Illuminate\Http\JsonResponse User skills object
     */
    public function getSkills($id)
    {
        $user = User::find($id);

        if (!$user) {
            throw new NotFoundException("User not found.");
        }

        $skills = $user->getSkills();

        return $this->respond(Response::HTTP_OK, $skills);
    }

    /**
     * Gets user statistics.
     *
     * @param $id Unique id of the user
     *
     * @throws NotFoundException
     *
     * @return \Illuminate\Http\JsonResponse User statistics object
     */
    public function getStatistics($id)
    {
        $user = User::find($id);

        if (!$user) {
            throw new NotFoundException("User not found.");
        }

        $statistics =  $user->getStatistics();

        return $this->respond(Response::HTTP_OK, $statistics);
    }

    /**
     * Gets user session comments.
     *
     * @param $id Unique id of the user
     *
     * @throws NotFoundException
     *
     * @return \Illuminate\Http\JsonResponse User comments object
     */
    public function getComments($id)
    {
        $user = User::find($id);

        if (!$user) {
            throw new NotFoundException("User not found.");
        }

        $comments = $user->getComments();

        return $this->respond(Response::HTTP_OK, $comments);
    }


    /**
     * Gets and formats user ratings
     *
     * @param $id
     *
     * @return object User ratings
     */
    private function getUserRating($id)
    {
        $rating = $this->usersAverageRatingRepository->getById($id);

        $response = (object)[
          "cumulative_average" => $rating->average_rating ?? 0,
          "mentee_average" => $rating->average_mentee_rating ?? 0,
          "mentor_average" => $rating->average_mentor_rating ?? 0,
          "rating_count" => $rating->session_count ?? 0,
        ];

        return $response;
    }

    /**
     * Gets a user rating details.
     *
     * @param integer $id - user id
     *
     * @return \Illuminate\Http\JsonResponse User ratings object
     */
    public function getRating($id)
    {
        $response = $this->getUserRating($id);

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Search for users on Lenken.
     *
     * @param Request $request
     * @throws NotFoundException
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $searchTerm = $request->input("q");
        $limit = $request->input("limit") ?
            intval($request->input("limit")) : 20;

        $users = User::where("email", "iLIKE", "%".$searchTerm."%")
                            ->paginate($limit);

        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user->id] = $user;
        }

        if (in_array("LENKEN_ADMIN", $request->user()->roles)) {
            $userEmails = array_column($users->items(), "email");

            if (count($userEmails) !== 0) {
                $aisUsers = $this->aisClient->getUsersByEmail($userEmails);

                foreach ($aisUsers["values"] as $aisUser) {
                    $usersById[$aisUser["id"]]["role"] = $aisUser["cohort"]["name"];
                    $usersById[$aisUser["id"]]["level"] = $aisUser["level"]["name"];
                }
            }
        }

        $response["users"] = array_values($usersById);
        $response["pagination"] = [
            "total_count" => $users->total(),
            "page_size" => $users->perPage()
        ];
        return $this->respond(Response::HTTP_OK, $response);
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
            $user["rating"] = (object)[
                "cumulative_average" => $ratingDetails->average_rating ?? 0,
                "mentee_average" => $ratingDetails->average_mentee_rating ?? 0,
                "mentor_average" => $ratingDetails->average_mentor_rating ?? 0,
                "rating_count" => $ratingDetails->session_count ?? 0
            ];

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

        $lenkenUser = User::find($id);

        $user = $this->formatUserInfo($userDetails);

        $user["skills"] = $lenkenUser->getSkills();

        return $user;
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
     * @param integer $userId  - user id
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
