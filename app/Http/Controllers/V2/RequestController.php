<?php
namespace App\Http\Controllers\V2;

use DB;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ConflictException;
use App\Models\Status;
use App\Models\Skill;
use App\Models\RequestSkill;
use App\Models\Request as MentorshipRequest;
use App\Models\RequestUsers;
use App\Models\Role;
use App\Models\RequestType;
use App\Models\RequestCancellationReason;
use App\Utility\SlackUtility;

/**
 * Class RequestController
 *
 * @package App\Http\Controllers
 */
class RequestController extends Controller
{
    use RESTActions;

    protected $slackUtility;

    public function __construct(SlackUtility $slackUtility)
    {
        $this->slackUtility = $slackUtility;
    }

    /**
     * Gets All Mentorship Requests which a user has not indicated interest in
     *
     * @param Request $request - request object
     *
     * @return array $response - A formatted array of Mentorship Requests
     */
    public function getRequestsPool(Request $request)
    {
        // Get all request params
        $userId = $request->user()->uid;

        $limit = $request->input("limit") ?
        intval($request->input("limit")) : 20;

        $params = $this->buildPoolFilterParams($request);

        $mentorshipRequests  = MentorshipRequest::buildPoolFilterQuery($params)
                ->whereNotIn(
                    "id", function ($query) use ($userId) {
                        $query->select("id")
                            ->from(with(new MentorshipRequest)->getTable())
                            ->whereRaw("interested::jsonb @> to_jsonb('".$userId."'::text)");
                    }
                )
                ->orderBy("created_at", "desc")
                ->paginate($limit);

        $response["requests"] = formatMultipleRequestsForAPIResponse($mentorshipRequests);
        $response["pagination"] = [
            "total_count" => $mentorshipRequests->total(),
            "page_size" => $mentorshipRequests->perPage()
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Gets All Mentorship Requests
     *
     * @param Request $request - request  object
     *
     * @return Array $response - A formatted array of Mentorship Requests
     */
    public function getAllRequests(Request $request)
    {
        $limit = $request->input("limit") ?
        intval($request->input("limit")) : 20;
        $params = $this->buildPoolFilterParams($request);

        $mentorshipRequests = MentorshipRequest::buildPoolFilterQuery($params)
            ->orderBy("created_at", "desc")
            ->paginate($limit);

        $response["requests"] = formatMultipleRequestsForAPIResponse($mentorshipRequests);
        $response["pagination"] = [
            "total_count" => $mentorshipRequests->total(),
            "page_size" => $mentorshipRequests->perPage()
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Returns request detail for a single request
     *
     * @param $id - id of the request
     *
     * @throws NotFoundException
     *
     * @return \HttpResponse $response - A formatted array of a single Request
     */
    public function getRequest($id)
    {
        $mentorshipRequest = MentorshipRequest::find(intval($id));

        if (!$mentorshipRequest) {
            throw new NotFoundException("Request not found.");
        }

        return $this->respond(
            Response::HTTP_OK,
            formatRequestForAPIResponse($mentorshipRequest)
        );
    }

    /**
     * Build query params from request
     *
     * @param Request $request - request object
     *
     * @return array $response - An associative array containing
     * query string data from request url
     */
    private function buildPoolFilterParams($request)
    {

        $params = [];

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

        if ($status = $this->getRequestParams($request, "status")) {
            $params["status"] = explode(",", $status);
        }

        if ($startDate = $this->getRequestParams($request, "startDate")) {
            $params["startDate"] = Carbon::createFromFormat("d-m-Y", $startDate);
        }

        if ($endDate = $this->getRequestParams($request, "endDate")) {
            $params["endDate"] = Carbon::createFromFormat("d-m-Y", $endDate);
        }

        return $params;
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

        $usersRequestsHistoryIds = RequestUsers::select("request_id")
                                            ->where("user_id", $userId)
                                            ->get();

        $mentorshipRequests = MentorshipRequest::where("status_id", STATUS::COMPLETED)
                                                    ->whereIn("id", $usersRequestsHistoryIds)
                                                    ->with("session.rating")
                                                    ->with("requestSkills")
                                                    ->get();
        $this->appendRating($mentorshipRequests);
        $formattedRequests = formatMultipleRequestsForAPIResponse($mentorshipRequests);
        return $this->respond(Response::HTTP_OK, $formattedRequests);
    }

    /**
     * Gets requests that are awaiting user to select mentor/mentee or
     * to be selected as mentor/mentee
     *
     * @param Request $request - request object
     *
     * @return Response Object
     */
    public function getPendingPool(Request $request)
    {
        $userId = $request->user()->uid;
        $requests = MentorshipRequest::where("created_by", $userId)
                                        ->where("status_id", 1)
                                        ->whereNotNull("interested")
                                        ->orWhereRaw("interested::jsonb @> to_jsonb('$userId'::text)")
                                        ->where("status_id", 1)
                                        ->orderBy("created_at", "desc")
                                        ->get();

        $formattedRequest =  formatMultipleRequestsForAPIResponse($requests);
        $this->appendAwaitedUser($formattedRequest, $userId);
        return $this->respond(Response::HTTP_OK, $formattedRequest);
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

        $usersCurrentRequestsIds = RequestUsers::select("request_id")
                                                    ->where("user_id", $userId)
                                                    ->get();

        $requestsInProgress = MentorshipRequest::whereIn("id", $usersCurrentRequestsIds)
                                                    ->where("status_id", STATUS::MATCHED)
                                                    ->orderBy("created_at", "desc")
                                                    ->get();
        $formattedRequestsInProgress = formatMultipleRequestsForAPIResponse($requestsInProgress);

        return $this->respond(Response::HTTP_OK, $formattedRequestsInProgress);
    }

    /**
     * Update mentorship request when a user indicates interest
     * by updating the request interested property
     *
     * @param Request $request - the request object
     * @param $id - the mentorship request ID
     *
     * @throws NotFoundException | BadRequestException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indicateInterest(Request $request, $id)
    {
        $currentUser = $request->user();
        $mentorshipRequest = MentorshipRequest::find(intval($id));

        if (!$mentorshipRequest) {
            throw new NotFoundException("Mentorship Request not found.");
        }

        $mentorshipRequest->interested = $mentorshipRequest->interested ?? [];

        if ($currentUser->uid === $mentorshipRequest->created_by->id) {
            throw new BadRequestException("You can't indicate interest in your own request.");
        }

        if (in_array($currentUser->uid, $mentorshipRequest->interested)) {
            throw new ConflictException("You have already indicated interest in this request.");
        }

        $mentorshipRequest->interested = array_merge($mentorshipRequest->interested, [$currentUser->uid]);
        $mentorshipRequest->save();

        return $this->respond(Response::HTTP_OK);
    }

    /**
     * Cancels a request for a mentorship that a logged in user requested
     * by setting the request status to cancelled
     *
     * @param Request $request - the request object
     * @param integer $id      - Unique ID used to identify the request
     *
     * @throws NotFoundException | ConflictException | UnauthorizedException
     */
    public function cancelRequest(Request $request, $id)
    {
        $currentUser = $request->user();
        $mentorshipRequest = $this->validateRequestBeforeCancellation($id, $currentUser);

        $mentorshipRequest->status_id = Status::CANCELLED;
        $cancellationReason = $request->input("reason");

        $result = DB::transaction(
            function () use ($mentorshipRequest, $currentUser, $cancellationReason) {
                $mentorshipRequest->save();
                if ($cancellationReason) {
                    RequestCancellationReason::create(
                        [
                            "request_id" => $mentorshipRequest->id,
                            "user_id" => $currentUser->uid,
                            "reason" => ucfirst($cancellationReason)
                        ]
                    );
                }
                $this->sendCancellationNotification($mentorshipRequest, $cancellationReason);
                return true;
            }
        );

        if ($result) {
            $this->respond(Response::HTTP_OK);
        }
    }

    /**
     * Validate whether mentorship request belongs to user and whether its
     * already cancelled before cancellation
     *
     * @param integer $id          - Mentorship Request ID
     * @param object  $currentUser - User requesting to cancel request
     *
     * @throws NotFoundException | ConflictException | UnauthorizedException
     *
     * @return object $mentorshipRequest
     */
    private function validateRequestBeforeCancellation($id, $currentUser)
    {
        $mentorshipRequest = MentorshipRequest::find(intval($id));
        if (!$mentorshipRequest) {
            throw new NotFoundException("Mentorship Request not found.");
        }

        if ($currentUser->role !== "Admin" && $currentUser->uid !== $mentorshipRequest->created_by->id) {
            throw new UnauthorizedException("You don't have permission to cancel this Mentorship Request.");
        }

        if ($mentorshipRequest->status_id == Status::CANCELLED) {
            throw new ConflictException("Mentorship Request already cancelled.");
        }
        return $mentorshipRequest;
    }

    /**
     * Send slack notification for cancelled request
     *
     * @param object $mentorshipRequest  - Cancelled request
     * @param string $cancellationReason - Reason for canceling request
     *
     * @return void
     */
    private function sendCancellationNotification($mentorshipRequest, $cancellationReason)
    {
        if ($mentorshipRequest->request_type_id === RequestType::MENTOR_REQUEST) {
            $createdBy = $mentorshipRequest->mentee;
        } else {
            $createdBy = $mentorshipRequest->mentor;
        }
        $requestTitle = $mentorshipRequest->title;
        $creationDate = $mentorshipRequest->created_at;
        $recipientSlackID = $createdBy["slack_id"];
        $slackMessage = "Your Mentorship Request `$requestTitle`
            opened on `$creationDate` has been cancelled \nREASON: `$cancellationReason`.";
        $this->slackUtility->sendMessage([$recipientSlackID], $slackMessage);
    }

    /**
     * Cancel interest in offering mentorship
     *
     * @param Request $request - the request object
     * @param integer $id      - Unique ID used to identify a request
     *
     * @throws NotFoundException | BadRequestException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdrawInterest(Request $request, $id)
    {
        $mentorshipRequest = MentorshipRequest::find(intval($id));

        if (!$mentorshipRequest) {
            throw new NotFoundException("Mentorship Request not found.");
        }

        $currentUser = $request->user();
        if (!$mentorshipRequest->interested || !in_array($currentUser->uid, $mentorshipRequest->interested)) {
            throw new BadRequestException("You don't have interest in this Mentorship Request");
        }

        $currentUserId = array_search($currentUser->uid, $mentorshipRequest->interested);

        $interested = $mentorshipRequest->interested;
        unset($interested[$currentUserId]);

        if (sizeof($interested) == 0) {
            $interested = null;
        }
        $mentorshipRequest->interested = $interested;

        $mentorshipRequest->save();

        return $this->respond(Response::HTTP_OK);
    }

    /**
     * Accept a user who has shown interest in users mentorship request.
     *
     * @param Request $request - Request object
     * @param integer $mentorshipRequestId - mentorship request id with interested user
     *
     * @throws NotFoundException if mentorship request is not found
     *
     * @return Response object
     */
    public function acceptInterestedUser(Request $request, $mentorshipRequestId)
    {
        $mentorshipRequest = MentorshipRequest::find($mentorshipRequestId);

        $this->validateBeforeAcceptOrRejectUser($request, $mentorshipRequest);

        $interestedUserId = $request->get("interestedUserId");

        $mentorshipRequest->match_date = Carbon::now();
        $mentorshipRequest->status_id = Status::MATCHED;
        $mentorshipRequest->save();

        if ($mentorshipRequest->request_type_id == RequestType::MENTOR_REQUEST) {
            $roleId = Role::MENTOR;
        } else {
            $roleId = Role::MENTEE;
        }

        DB::table("request_users")->insert(
            [
                "user_id" => $interestedUserId,
                "request_id" => $mentorshipRequestId,
                "role_id" => $roleId
            ]
        );

        return $this->respond(Response::HTTP_OK, $mentorshipRequest);
    }

    /**
     * Reject a user who has shown interest in user's mentorship request
     *
     * @param Request $request             - Request object
     * @param integer $mentorshipRequestId - mentorship request id with interested user
     *
     * @throws NotFoundException if mentorship request is not found
     *
     * @return Response object
     */
    public function rejectInterestedUser(Request $request, $mentorshipRequestId)
    {
        $mentorshipRequest = MentorshipRequest::find($mentorshipRequestId);

        $this->validateBeforeAcceptOrRejectUser($request, $mentorshipRequest);

        $allInterestedUsers = $mentorshipRequest->interested ?? [];
        $interestedUserId = $request->get("interestedUserId");

        $interestedUserIndex = array_search($interestedUserId, $allInterestedUsers);
        array_splice($allInterestedUsers, $interestedUserIndex, 1);
        $mentorshipRequest->interested
            = count($allInterestedUsers) > 0 ? $allInterestedUsers : null;
        $mentorshipRequest->save();

        return $this->respond(Response::HTTP_OK, $mentorshipRequest);
    }

    /**
     * Check validity of request in accept and reject interested user functions, if not
     * throw Exception
     *
     * @param Request $request - Request object
     * @param MentorshipRequest $mentorshipRequest - A mentorship request object
     *
     * @throws BadRequestException - if mentorship request is not open
     * @throws AccessDeniedException - if user does not own the mentorship request
     * @throws NotFoundException - if interested user given is not an interested user
     *
     * @return void
     */
    private function validateBeforeAcceptOrRejectUser(Request $request, MentorshipRequest $mentorshipRequest)
    {
        $this->validate($request, MentorshipRequest::$acceptOrRejectUserRules);

        if (!($mentorshipRequest)) {
            throw new NotFoundException("The mentorship request was not found");
        }

        if (!$mentorshipRequest->status_id === Status::OPEN) {
            throw new BadRequestException("Operation can only be performed on open requests");
        }

        $currentUser = $request->user();
        if ($currentUser->uid !== $mentorshipRequest->created_by->id) {
            throw new AccessDeniedException("You do not have permission to perform this operation");
        }

        $interestedUserId = $request->get("interestedUserId");
        $allInterestedUsers = $mentorshipRequest->interested ?? [];
        if (!(in_array($interestedUserId, $allInterestedUsers))) {
            throw new NotFoundException("The fellow is not an interested user");
        }
    }

    /**
     * Creates a new Mentorship request and saves it in the request table
     *
     * @param Request $request - request object
     *
     * @throws InternalServerErrorException
     *
     * @return object Response object of created request
     */
    public function createRequest(Request $request)
    {
        $this->validate($request, MentorshipRequest::$rules);
        $user = $request->user();

        $requestDetails = $this->removeSkillsFields($request->all());
        $requestDetails["status_id"] = Status::OPEN;
        $requestDetails["created_by"] = $user->uid;

        $requestSkills['primary'] = $request->input('primary');
        $requestSkills['secondary'] = $request->input('secondary');
        $requestSkills['preRequisite'] = $request->input('preRequisite');

        $requestDetails["request_type_id"] = (int)$request->input('requestType');
        if ($requestDetails["request_type_id"] === RequestType::MENTOR_REQUEST) {
            $userRole = Role::MENTEE;
        } else {
            $userRole = Role::MENTOR;
        }

        $result = DB::transaction(
            function () use ($requestDetails, $requestSkills, $userRole) {
                $createdRequest = MentorshipRequest::create($requestDetails);

                $requestSkills = $this->saveRequestSkills($requestSkills, $createdRequest);

                RequestUsers::create(
                    [
                        "user_id" => $createdRequest->created_by->id,
                        "request_id" => $createdRequest->id,
                        "role_id" => $userRole
                    ]
                );

                $createdRequest->request_skills = formatRequestSkills($requestSkills);

                return $createdRequest;
            }
        );

        return $this->respond(Response::HTTP_CREATED, formatRequestForAPIResponse($result));
    }

    /**
     * Save request skills in the request_skills table
     *
     * @param array $requestSkills - request skill
     * @param object $createdRequest - created request
     *
     * @return void
     */
    private function saveRequestSkills($requestSkills, $createdRequest)
    {
        RequestSkill::mapRequestToSkill(
            $createdRequest->id,
            $requestSkills['primary'],
            'primary'
        );

        if ($createdRequest->request_type_id === RequestType::MENTOR_REQUEST) {
            $skills = $requestSkills['secondary'];
            $requestSkillType = 'secondary';
        } else {
            $skills = $requestSkills['preRequisite'];
            $requestSkillType = 'preRequisite';
        }

        RequestSkill::mapRequestToSkill(
            $createdRequest->id,
            $skills,
            $requestSkillType
        );

        $requestSkills = RequestSkill::where(
            "request_id",
            $createdRequest->id
        )
        ->with("skill")->get();

        return $requestSkills;
    }

    /**
     * Retrieve all requests for a particular skill in the database
     *
     * @param integer $skilId - skill id
     *
     * @throws BadRequestException
     *
     * @return json - JSON object containing skill(s) request
     */
    public function getSkillRequests($skillId)
    {
        if ((!filter_var($skillId, FILTER_VALIDATE_INT)) || (empty($skillId))) {
            throw new BadRequestException("Invalid parameter.");
        }

        $skill = Skill::select("name", "id")->where("id", $skillId)->first();

        $field = ["skill_id" => $skillId, "type" => "primary"];

        $requestIds = RequestSkill::select("request_id")->where($field);
        $requests = MentorshipRequest::whereIn("id", $requestIds)->get();

        $formattedRequests = formatMultipleRequestsForAPIResponse($requests);
        $skill["requests"] = $formattedRequests;

        return $this->respond(Response::HTTP_OK, ["skill" => $skill]);
    }

    /**
     * Calculate and attach the rating of each request Object
     *
     * @param object $mentorshipRequests - mentorship requests
     *
     * @return void
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
     * Append the user being awaited
     *
     * @param array  $requests - mentorship requests
     * @param String $userId   - id of current user
     *
     * @return void
     */
    private function appendAwaitedUser($requests, $userId)
    {
        foreach ($requests as $request) {
            $awaitedUser = "you";
            if (in_array($userId, $request->interested)) {
                $awaitedUser = $request->mentee->fullname;
            }
            $request->awaited_user = $awaitedUser;
        }
    }

    /**
     * Filter incoming request body to remove object property
     * containing primary and secondary skills or
     * primary and prerequisite skills
     *
     * @param object $request the request
     *
     * @return object
     */
    private function removeSkillsFields($request)
    {
        return array_filter(
            $request,
            function ($key) {
                return $key !== "primary" && $key !== "secondary";
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
