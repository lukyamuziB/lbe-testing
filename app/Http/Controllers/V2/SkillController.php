<?php

namespace App\Http\Controllers\V2;

use App\Clients\AISClient;
use App\Models\Skill;
use App\Exceptions\Exception;
use Illuminate\Http\Response;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;
use App\Models\Request as MentorshipRequest;
use App\Models\RequestSkill;
use App\Models\RequestUsers;
use App\Models\Role;
use Carbon\Carbon;
use App\Exceptions\ConflictException;
use App\Repositories\LastActiveRepository;
use Illuminate\Http\Request;
use App\Repositories\UsersAverageRatingRepository;
use App\Repositories\PendingSkillsRepository;

/**
 * Class SkillController
 *
 * @package App\Http\Controllers\V2
 */
class SkillController extends Controller
{
    use RESTActions;
    private $lastActiveRepository;
    private $pendingSkillsRepository;

    public function __construct(
        LastActiveRepository $lastActiveRepository,
        PendingSkillsRepository $pendingSkillsRepository
    ) {
        $this->lastActiveRepository = $lastActiveRepository;
        $this->pendingSkillsRepository = $pendingSkillsRepository;
    }

    /**
     * Adds a new skill to the skills table
     *
     * @param Request $request - the request object
     *
     * @throws ConflictException
     *
     * @return Response object
     */
    public function addSkill(Request $request)
    {
        $this->validate($request, Skill::$rules);

        if (Skill::where('name', 'ilike', $request->name)->exists()) {
            throw new ConflictException("Skill already exists.");
        }
        $skill = Skill::create(
            [
                "name" => $request->name
            ]
        );

        return $this->respond(Response::HTTP_CREATED, $skill);
    }

    /**
     * GET all skills that have at least one request
     *
     * @return json - JSON object containing skill(s)
     */
    public function getSkillsWithRequests()
    {
        $skills = Skill::whereHas("requestSkills")->get();

        return $this->respond(Response::HTTP_OK, $skills);
    }

    /**
     * Retrieve all skills in the database including the ones that have been
     * soft deleted with requestSkills.
     *
     * @param Request $request - HTTP Request object
     *
     * @return json - JSON object containing skill(s)
     */
    public function getSkills(Request $request)
    {
        $isTrashed = $request->input("isTrashed");

        $searchQuery = $this->getRequestParams($request, "q");

        if (strval($isTrashed) === "true") {
            $skills = Skill::when(
                $searchQuery,
                function ($query) use ($searchQuery) {
                    $query->where("name", "ilike", "%$searchQuery%");
                }
            )
                ->withTrashed()->with(["requestSkills"])
                ->orderBy("name", "asc")->get();
        } else {
            $skills = Skill::when(
                $searchQuery,
                function ($query) use ($searchQuery) {
                    $query->where("name", "ilike", "%$searchQuery%");
                }
            )
                ->with(["requestSkills"])
                ->orderBy("name", "asc")->get();
        }

        return $this->respond(Response::HTTP_OK, $skills);
    }

    /**
     * Appends a mentors last active time for each mentor in
     * the mentors details object.
     *
     * @param array $mentorsIds - ids of mentors
     * @param array $mentorsDetails - details of mentors
     *
     * @return json - JSON object containing the updated mentors details
     */
    private function appendMentorsLastActive(&$mentorsDetails)
    {
        $lastActives = $this->lastActiveRepository->query(
            array_column($mentorsDetails, "user_id")
        );

        foreach ($mentorsDetails as &$mentorDetail) {
            $mentorDetail->last_active =
                Carbon::parse($lastActives[$mentorDetail->user_id])->toFormattedDateString();
        }
        return $mentorsDetails;
    }

    /**
     * Appends the no of mentorships for each mentor to the mentor
     * details object.
     *
     * @param array $mentorsIds - ids of mentors
     * @param array $mentorsDetails - details of mentors
     *
     * @return json - JSON object containing the updated mentor details
     */
    private function appendMentorshipsCount($mentorsIds, &$mentorsDetails)
    {
        foreach ($mentorsDetails as &$mentorDetail) {
            $mentorDetail->mentorships_count = count(array_keys($mentorsIds, $mentorDetail->user_id));
        }
        return $mentorsDetails;
    }

    /**
     * Retrieve mentors for a particular skill in the database
     *
     * @param integer $skilId - skill id
     *
     * @throws BadRequestException
     *
     * @return json - JSON object containing skill(s) mentors
     */
    public function getSkillMentors(
        AISClient $aisClient,
        UsersAverageRatingRepository $usersAverageRatingRepository,
        Request $request,
        $skillId
    ) {
        if ((!filter_var($skillId, FILTER_VALIDATE_INT)) || (empty($skillId))) {
            throw new BadRequestException("Invalid parameter.");
        }

        $limit = $request->query("limit");

        if (!$limit) {
            $limit = 4;
        }

        $field = ["skill_id" => $skillId, "type" => "primary"];

        $skill = Skill::select("name", "id")->where("id", $skillId)->first();
        $requestIds = RequestSkill::select("request_id")->where($field);
        $mentorsIds = RequestUsers::whereIn("request_id", $requestIds)
            ->where("role_id", Role::MENTOR)
            ->with("user")
            ->get();

        $uniqueMentorIds = array_unique(array_map(
            function ($mentorsId) {
                return $mentorsId["user_id"];
            },
            $mentorsIds->toArray()
        ));

        $mentorsAverageRating = $usersAverageRatingRepository->query($uniqueMentorIds);

        usort($mentorsAverageRating, function ($firstElement, $secondElement) {
            return ($firstElement->average_mentor_rating > $secondElement->average_mentor_rating) ? -1 : 1;
        });

        array_splice($mentorsAverageRating, $limit);

        $mentorsEmail = $this->getMentorsEmail($mentorsIds);

        $aisMentorsDetails = $aisClient->getUsersByEmail($mentorsEmail);
        $mentorsDetails = $this->appendMentorAvatarAndNameToRatings(
            $mentorsAverageRating,
            $aisMentorsDetails["values"]
        );

        $this->appendMentorsLastActive($mentorsDetails);
        $this->appendMentorshipsCount(
            $uniqueMentorIds,
            $mentorsDetails
        );

        $skill["mentors"] = $mentorsDetails;

        return $this->respond(Response::HTTP_OK, ["skill" => $skill]);
    }

    /**
     * Modifies the mentors array by appending user's picture and name where the email matches
     *
     * @param array $mentors to modify
     * @param array $aisMentorsDetails array with mentor ais details
     *
     * @return array $mentors - list of mentors with their pictures and names
     */
    private function appendMentorAvatarAndNameToRatings($mentors, $aisMentorsDetails)
    {
        $mentorsDetails = [];
        foreach ($mentors as $mentor) {
            $name = "";
            $picture = "";
            foreach ($aisMentorsDetails as $details) {
                if ($mentor->user_id === $details["id"]) {
                    $name = $details["name"];
                    $picture = $details["picture"];
                }
            }
            $mentor->name = $name;
            $mentor->picture = $picture;
            unset($mentor->email);
            $mentorsDetails[] = $mentor;
        }

        return $mentorsDetails;
    }

    /**
     * Gets mentors email
     *
     * @param array $mentors details
     *
     * @return array $mentorsEmail
     */
    private function getMentorsEmail($mentors)
    {
        $mentorsEmail = [];
        foreach ($mentors as $mentor) {
            $email = $mentor->user->email;
            $mentorsEmail[] = $email;
        }
        return $mentorsEmail;
    }

    /** Gets status count for each skill requested by location and start date and end date
     *
     * @param Request $request - the request object
     *
     * @throws BadRequestException
     *
     * @return Response object
     */
    public function getSkillsAndStatusCount(Request $request)
    {
        $params = [];
        $startDate = $this->getRequestParams($request, "start_date");
        $endDate = $this->getRequestParams($request, "end_date");

        if (($startDate && $endDate)) {
            if ((Carbon::createFromFormat("Y-m-d", $startDate)->lt(Carbon::createFromFormat("Y-m-d", $endDate)))
            ) {
                $params["startDate"] = Carbon::createFromFormat("Y-m-d", $startDate);
                $params["endDate"] = Carbon::createFromFormat("Y-m-d", $endDate);
            } else {
                throw new BadRequestException("start_date cannot be greater than end_date.");
            }
        }

        if ($location = $this->getRequestParams($request, "location")) {
            $params["location"] = $location;
        }

        $mentorshipRequests = MentorshipRequest::buildQuery($params)
            ->get();

        // Build the response object
        $response = $this->getEachSkillStatusCount($mentorshipRequests);

        return $this->respond(Response::HTTP_OK, $response);
    }


    /**
     * Adds a pending skill to the database
     *
     * @param Request $request - HTTPRequest object
     *
     * @throws BadRequestException
     *
     * @return JSON - Details of the added skill and the user associated with it
     */
    public function addPendingSkill(Request $request)
    {
        $skill = $request->skill;
        $userId = $request->userId;

        if (!$userId && !$skill) {
            throw new BadRequestException("Invalid parameters.");
        }

        return $this->respond(Response::HTTP_CREATED, $this->pendingSkillsRepository->add($skill, $userId));
    }

    /**
     * Get all pending skills
     *
     * @return JSON - Array of all the pending skills
     */
    public function getAllPendingSkills()
    {
        $pendingSkills = $this->pendingSkillsRepository->getAll();

        return $this->respond(Response::HTTP_OK, $pendingSkills);
    }

    /**
     * Get all users with pending skills
     *
     * @return JSON - Array of all Users with pending skills
     */
    public function getAllUsers()
    {
        $users = $this->pendingSkillsRepository->getAllUsers();

        return $this->respond(Response::HTTP_OK, $users);
    }

    /**
     * Return a skill and all the associated users
     *
     * @param string $skill - the name of the skill
     *
     * @throws BadRequestException|NotFoundException
     *
     * @return JSON - A skill and the details of all the associated users
     */
    public function getPendingSkillDetails($skill)
    {
        $decodedSkill = urldecode($skill);

        if (!$decodedSkill) {
            throw new BadRequestException("Invalid parameter");
        }

        return $this->respond(Response::HTTP_OK, $this->pendingSkillsRepository->getById($decodedSkill));
    }

    /**
     * Gets a user's pending skills
     *
     * @param string $userId - unique ID of the user
     *
     * @throws BadRequestException
     *
     * @return JSON - A user and the skills they have requested
     */
    public function getUserSkills($userId)
    {
        if (!$userId) {
            throw new BadRequestException("Invalid parameter");
        }

        $userSkills = $this->pendingSkillsRepository->getUserSkills($userId);

        return ($this->respond(Response::HTTP_OK, $userSkills));
    }

    /**
     * Compute the frequency of occurrence for skill statuses
     *
     * @param array $mentorshipRequests array of requests made for that period and location
     *
     * @return array of skills and the number of occurrences of skill status
     */
    private function getEachSkillStatusCount($mentorshipRequests)
    {
        $requestSkillWithStatus = [];
        $eachSkillWithStatusCount = [];

        foreach ($mentorshipRequests as $mentorshipRequest) {
            $status = $mentorshipRequest->status->name;

            foreach ($mentorshipRequest->requestSkills as $skill) {
                $requestSkillWithStatus[$skill->skill->name][] = $status;
            }
        }

        foreach ($requestSkillWithStatus as $skill => $skillStatusList) {
            $countMatchingStatus = array_count_values($skillStatusList);
            $eachSkillWithStatusCount[] = ["name" => $skill, "count" => $countMatchingStatus];
        }

        return $eachSkillWithStatusCount;
    }

    /**
     * It enables or disables a skill based on the status property set.
     *
     * @param integer $id - Unique ID of a particular skill.
     *
     * @throws BadRequestException | NotFoundException.
     *
     * @return object response of success or error message.
     */
    public function updateSkillStatus(Request $request, $id)
    {
        $status = $request->input('status');

        if ($status === null) {
            throw new BadRequestException("Invalid parameters.");
        }

        $skill = Skill::withTrashed()->find($id);

        if (!$skill) {
            throw new NotFoundException("Skill not found.");
        }

        if ($status === "active") {
            $skill->restore();
        } elseif ($status === "inactive") {
            $skill->delete();
        } else {
            throw new BadRequestException("Invalid parameters.");
        }

        return $this->respond(Response::HTTP_OK);
    }
}
