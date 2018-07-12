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
use App\Models\User;
use App\Models\Skill;
use App\Models\UserSkill;
use App\Models\RequestSkill;
use App\Models\Request as MentorshipRequest;
use App\Models\RequestUsers;
use App\Models\Role;
use App\Models\UserNotification;
use App\Models\RequestType;
use App\Models\RequestStatusUpdateReasons;
use App\Utility\SlackUtility;
use App\Clients\GoogleCalendarClient;
use App\Mail\NewRequestMail;
use App\Mail\CancelRequestMail;
use App\Mail\MentorIndicatesInterestMail;
use App\Mail\MenteeWithdrawsInterestMail;
use App\Mail\UserAcceptanceOrRejectionNotificationMail;
use App\Mail\MentorRequestMail;

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

        $mentorshipRequests = MentorshipRequest::buildPoolFilterQuery($params)
            ->whereNotIn(
                "id",
                function ($query) use ($userId) {
                    $query->select("id")
                        ->from(with(new MentorshipRequest)->getTable())
                        ->whereRaw("interested::jsonb @> to_jsonb('" . $userId . "'::text)");
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
     * Gets requests based on search term supplied
     *
     * @param Request $request - request  object
     *
     * @return Array $response - A formatted array of  Requests
     */
    public function searchRequests(Request $request)
    {
        $limit = $request->input("limit") ? intval($request->input("limit")) : 20;

        if (!$q = $this->getRequestParams($request, "q")) {
            throw new BadRequestException("No search query was given.");
        }

        $splitSearchQuery = explode(" ", $q);

        $selectedUserIds = User::select("id")
            ->where(
                function ($query) use ($splitSearchQuery) {
                    $this->searchQueryCallback($query, $splitSearchQuery, "email");
                }
            );

        $searchResults = MentorshipRequest::where(
            function ($query) use ($splitSearchQuery) {
                $this->searchQueryCallback($query, $splitSearchQuery, "title");
            }
        )
            ->orWhere(
                function ($query) use ($splitSearchQuery) {
                    $this->searchQueryCallback($query, $splitSearchQuery, "description");
                }
            )
            ->orWhereIn("created_by", $selectedUserIds)
            ->orWhereIn("id", $this->getComplementarySkillsMatchingSearchTerm($q))
            ->orderBy("created_at", "desc")
            ->paginate($limit);

        $formattedSearchRequests = formatMultipleRequestsForAPIResponse($searchResults);
        $response["requests"] = $formattedSearchRequests;
        $response["pagination"] = [
            "total_count" => $searchResults->total(),
            "page_size" => $searchResults->perPage(),
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
     * Query callback
     *
     * @param - splitSearchParams
     *
     * @return void
     */
    private function searchQueryCallback($query, $splitSearchParams, $field)
    {
        foreach ($splitSearchParams as $param) {
            $query->orwhere($field, "ILIKE", "%" . $param . "%");
        }
    }

    /**
     * Return the requestIds for requests where the complementary skill matches the search term
     *
     * @param string $skillName - the name of the skill to be searched
     *
     * @return array $requestIds - requestIds for all requests with complementary the skill matching the search term
     */
    private function getComplementarySkillsMatchingSearchTerm($skillName)
    {
        $skillsIds = Skill::select("id")
            ->where("name", "ILike", $skillName);

        $requestIds = RequestSkill::select("request_id")
            ->where('type', 'secondary')
            ->whereIn('skill_id', $skillsIds);

        return $requestIds;
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
        if ($category = $this->getRequestParams($request, "category")) {
            $params["category"] = $category;
        }

        if ($requestTypes = $this->getRequestParams($request, "type")) {
            $params["types"] = array_map('intval', explode(",", $requestTypes));
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

        if ($ratings = $this->getRequestParams($request, "ratings")) {
            $params["ratings"] = explode(",", $ratings);
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
            ->where("user_id", $userId);

        $mentorshipRequests = MentorshipRequest::whereIn("status_id", [Status::COMPLETED, Status::ABANDONED])
            ->whereIn("id", $usersRequestsHistoryIds)
            ->with(["session.rating", "requestSkills"])
            ->get();

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
            ->where("status_id", STATUS::OPEN)
            ->whereNotNull("interested")
            ->orWhereRaw("interested::jsonb @> to_jsonb('$userId'::text)")
            ->where("status_id", STATUS::OPEN)
            ->orderBy("created_at", "desc")
            ->get();

        $formattedRequest = formatMultipleRequestsForAPIResponse($requests);

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
            ->where("user_id", $userId);

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

        $this->sendIndicatesInterestMail($mentorshipRequest, $currentUser);
        return $this->respond(Response::HTTP_OK);
    }

    /**
     * Sends an email to requester indicating interest in their request
     *
     * @param Object $mentorshipRequest - Indicates interest request
     * @param Object $currentUser - A user currently indicating interest
     */
    private function sendIndicatesInterestMail($mentorshipRequest, $currentUser)
    {
        $indicateInterestDetails = [
            "currentUser" => $currentUser->name,
            "title" => $mentorshipRequest->title,
            "request_type" => $mentorshipRequest->request_type_id == Role::MENTOR ? "mentor" : "mentee",
        ];

        $recipientEmail = $mentorshipRequest->created_by->email;
        if (!empty($recipientEmail)) {
            $mentorIndicatesInterestMail = new MentorIndicatesInterestMail($indicateInterestDetails, $recipientEmail);
            return sendEmailNotification($recipientEmail, $mentorIndicatesInterestMail);
        }
    }

    /**
     * Cancels a request for a mentorship that a logged in user requested
     * by setting the request status to cancelled
     *
     * @param Request $request - the request object
     * @param integer $id - Unique ID used to identify the request
     *
     * @throws NotFoundException | ConflictException | UnauthorizedException
     */
    public function cancelRequest(Request $request, $id)
    {
        $currentUser = $request->user();

        $mentorshipRequest = MentorshipRequest::validateRequestBeforeCancellation($id, $currentUser);

        $mentorshipRequest->status_id = Status::CANCELLED;

        $cancellationReason = $request->input("reason");

        $result = DB::transaction(
            function () use ($mentorshipRequest, $currentUser, $cancellationReason) {
                $mentorshipRequest->save();

                if ($cancellationReason) {
                    RequestStatusUpdateReasons::create(
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

        $this->sendCancelRequestEmail($mentorshipRequest, $currentUser);

        if ($result) {
            $this->respond(Response::HTTP_OK);
        }
    }

    /**
     * Sends an email to users interested in a canceled request
     *
     * @param Object $mentorshipRequest .
     */
    private function sendCancelRequestEmail($mentorshipRequest, $currentUser)
    {
        $payload = [
            "currentUser" => $currentUser->name,
            "title" => $mentorshipRequest->title,
            "request_type" => $mentorshipRequest->request_type_id == 1 ? "mentor" : "mentee",
        ];

        $interestedUserEmails = User::whereIn("id", $mentorshipRequest->interested)->pluck("email");

        if (!empty($interestedUserEmails)) {
            foreach ($interestedUserEmails as $userEmail) {
                $emailPayload = new CancelRequestMail($payload, $userEmail);

                return sendEmailNotification($userEmail, $emailPayload);
            }
        }
    }

    /**
     * Send slack notification for cancelled request
     *
     * @param object $mentorshipRequest - Cancelled request
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
     * Updates the status of a mentorship request
     * by setting the request status to be cancelled or abandoned
     *
     * @param Request $request - the request object
     * @param integer $id - Unique ID used to identify the request
     *
     * @throws NotFoundException | ConflictException | UnauthorizedException | BadRequestException
     */
    public function updateStatus(Request $request, $id)
    {
        $currentUser = $request->user();

        $newStatus = $request->input("status");

        if ($newStatus === Status::CANCELLED) {
            $mentorshipRequest = MentorshipRequest::validateRequestBeforeCancellation($id, $currentUser);
        } else if ($newStatus === Status::ABANDONED) {
            $mentorshipRequest = MentorshipRequest::validateRequestBeforeAbandon($id, $currentUser);
        } else {
            throw new BadRequestException("No request status.");
        }

        $mentorshipRequest->status_id = $newStatus;

        $statusUpdateReason = $request->input("reason");

        $result = DB::transaction(
            function () use ($mentorshipRequest, $currentUser, $statusUpdateReason) {
                $mentorshipRequest->save();

                if ($statusUpdateReason) {
                    RequestStatusUpdateReasons::create(
                        [
                            "request_id" => $mentorshipRequest->id,
                            "user_id" => $currentUser->uid,
                            "reason" => ucfirst($statusUpdateReason)
                        ]
                    );
                }

                return $mentorshipRequest;
            }
        );

        return $this->respond(Response::HTTP_OK, $result);
    }

    /**
     * Cancel interest in offering mentorship
     *
     * @param Request $request - the request object
     * @param integer $id - Unique ID used to identify a request
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
        $this->sendWithdrawEmail($mentorshipRequest, $currentUser);
        return $this->respond(Response::HTTP_OK);
    }

    /**
     * Sends an email to users(mentor/mentee) that a request is withdrawn
     *
     * @param Object $mentorshipRequest - Withdraw Request
     * @param Object $currentUser - A currently withdrawing user
     */
    private function sendWithdrawEmail($mentorshipRequest, $currentUser)
    {
        $withdrawEmailDetails = [
            "currentUser" => $currentUser->name,
            "title" => $mentorshipRequest->title,
            "request_type" => $mentorshipRequest->request_type_id == 1 ? "mentor" : "mentee",
        ];

        $recipientEmail = $mentorshipRequest->created_by->email;
        if (!empty($recipientEmail)) {
            $menteeWithdrawsInterestMail = new MenteeWithdrawsInterestMail($withdrawEmailDetails, $recipientEmail);
            return sendEmailNotification($recipientEmail, $menteeWithdrawsInterestMail);
        }
    }

    /**
     * Accept a user who has shown iterest in users mentorship request.
     *
     * @param Request $request - Request object
     * @param GoogleCalendarClient $googleCalendar google client
     * @param integer $mentorshipRequestId - mentorship request id with interested user
     *
     * @throws NotFoundException|AccessDeniedException|BadRequestException
     *
     * @return Response object
     */
    public function acceptInterestedUser(Request $request, GoogleCalendarClient $googleCalendar, $mentorshipRequestId)
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

        schedulePairingSessionsOnCalendar(
            $googleCalendar,
            $mentorshipRequest
        );

        $this->sendUserActionNotification(
            $mentorshipRequest,
            UserNotification::ACCEPT_USER
        );

        return $this->respond(Response::HTTP_OK, $mentorshipRequest);
    }

    /**
     * Reject a user who has shown interest in user's mentorship request
     *
     * @param Request $request - Request object
     * @param integer $mentorshipRequestId - mentorship request id with interested user
     *
     * @throws NotFoundException|AccessDeniedException|BadRequestException
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

        $this->sendUserActionNotification(
            $mentorshipRequest,
            UserNotification::REJECT_USER,
            $interestedUserId
        );

        return $this->respond(Response::HTTP_OK, $mentorshipRequest);
    }

    /**
     * Checks the type of notification to be sent, whether it's a rejection
     * or acceptance notification, composes the payload and finally
     * sends the notification email
     *
     * @param Object $request - the mentorship request
     * @param String $notificationType - the type of notification to send
     * @param String $interestedUserId - Id of a user who has shown interest
     *
     * @return {Object} email object
     */
    private function sendUserActionNotification($request, $notificationType, $interestedUserId = null)
    {
        $recipient = "";

        $request->request_type_id === RequestType::MENTOR_REQUEST
            ? $roleId = Role::MENTOR : $roleId = Role::MENTEE;

        if ($interestedUserId) {
            $$recipient = User::select("email")
                ->where("id", $interestedUserId)
                ->get();
        } else {
            $recipient = $roleId === Role::MENTOR
                ? $request->mentor->email : $request->mentee->email;
        }

        $emailDetails = [
            "fullname" => $request->created_by->fullname,
            "requestedSkill" => $request->title,
            "userRole" => $roleId == Role::MENTOR ? 'Mentor' : 'Mentee',
        ];

        if ($notificationType === UserNotification::ACCEPT_USER) {
            $emailDetails["notificationType"] = UserNotification::ACCEPT_USER;
            $emailDetails["emailSubject"] = "Interested User Acceptance Notification";
        } else {
            $emailDetails["notificationType"] = UserNotification::REJECT_USER;
            $emailDetails["emailSubject"] = "Interested User Rejection Notification";
        }

        $emailInstance = new UserAcceptanceOrRejectionNotificationMail($recipient, $emailDetails);

        return sendEmailNotification($recipient, $emailInstance);
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

        $payload = [
            "currentUser" => $user->name,
            "title" => $requestDetails["title"]
        ];

        if ($result && $requestDetails["request_type_id"] === RequestType::MENTEE_REQUEST) {
            $openMentorshipRequests = $this->getOpenMentorshipRequests($requestDetails);
            $this->notifyMatchingRequestAuthors($openMentorshipRequests, $payload);
        } elseif ($result && $requestDetails["request_type_id"] === RequestType::MENTOR_REQUEST) {
            $skills = array_merge(
                $requestSkills['secondary'],
                $requestSkills['primary'],
                $requestSkills['preRequisite']
            );
            $mentorEmails = $this->getMentorsEmailsBySkills($skills);
            $this->notifyMentorsWithSkills($mentorEmails, $payload);
        }
        return $this->respond(Response::HTTP_CREATED, formatRequestForAPIResponse($result));
    }

    /**
     * Gets a list of user'semails who possess the skills requested.
     *
     * @param Array $skillIds - primary skills ids list
     *
     * @return Array Response of created request
     */
    private function getMentorsEmailsBySkills($skillIds)
    {
        $mentorIds = UserSkill::whereIn('skill_id', $skillIds)->pluck('user_id');
        $userEmails = User::whereIn('id', $mentorIds)->pluck('email');
        return $userEmails;
    }

    /**
     * Notifies users who possess the required skills of the newly created request
     *
     * @param Array $mentorEmails - List of matching mentorship emails.
     *
     * @param Array $payload - notification payload.
     *
     * @return void Response object of created request
     */
    private function notifyMentorsWithSkills($mentorEmails, $payload)
    {
        foreach ($mentorEmails as $mentorEmail) {
            $this->sendMentorRequestNotification($mentorEmail, $payload);
        }
    }

    /**
     * Sends an email to users with required skills
     *
     * @param String $recipient - Email recipient
     * @param Object requestPayload - Email payload.
     */
    private function sendMentorRequestNotification($recipient, $requestPayload)
    {
        $payload = new MentorRequestMail($requestPayload, $recipient);
        return sendEmailNotification($recipient, $payload);
    }

    /**
     * Gets a list users with matching open mentorship requests.
     *
     * @param Object $requestDetails - Request details object
     */
    private function getOpenMentorshipRequests($requestDetails)
    {
        return MentorshipRequest::where("status_id", STATUS::OPEN)
            ->where("request_type_id", RequestType::MENTOR_REQUEST)
            ->where("title", $requestDetails["title"])
            ->get();
    }

    /**
     * Notifies authors of the matching mentee request
     *
     * @param $openMentorshipRequests - List of matching mentorship request.
     * @param $payload - notification payload.
     */
    private function notifyMatchingRequestAuthors($openMentorshipRequests, $payload)
    {
        foreach ($openMentorshipRequests as $openMentorshipRequest) {
            $this->sendMenteeRequestNotification($openMentorshipRequest["created_by"]["email"], $payload);
        }
    }

    /**
     * Sends an email to users with open mentor request
     *
     * @param String $recipient - Email recipient
     * @param Object $payload - Email payload.
     */
    private function sendMenteeRequestNotification($recipient, $requestPayload)
    {
        $payload = new NewRequestMail($requestPayload, $recipient);

        return sendEmailNotification($recipient, $payload);
    }

    /**
     * Edit a mentorship request by updating the request table if no new request_skills exist,
     * otherwise both request and request_skills tables are updated
     *
     * @param Request $request - HTTPRequest object
     * @param integer $id - unique id of the mentorship request
     *
     * @throws NotFoundException  | AccessDeniedException |  BadRequestException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editRequest(Request $request, $id)
    {
        $this->validate($request, MentorshipRequest::$rules);

        $existingRequest = MentorshipRequest::find(intval($id));

        if (!$existingRequest) {
            throw new NotFoundException("Mentorship request not found.");
        }

        $currentUser = $request->user();

        if ($currentUser->uid !== $existingRequest->created_by->id) {
            throw new AccessDeniedException("You do not have permission to edit this mentorship request.");
        }

        if ($existingRequest->status_id !== Status::OPEN) {
            throw new BadRequestException("You can only edit an open request.");
        }

        $requestDetails = $this->removeSkillsFields($request->all());

        $requestSkills["primary"] = $request->input("primary");

        $requestSkills["secondary"] = $request->input("secondary");

        $requestSkills["preRequisite"] = $request->input("preRequisite");

        $currentSkills = $existingRequest->requestSkills
            ->pluck("skill_id")
            ->toArray();
        $newSkillsPresent = $this->areNewSkillsAdded($currentSkills, $requestSkills);

        if (!$newSkillsPresent) {
            $existingRequest->update($requestDetails);
            $response = $existingRequest;
        } else {
            $response = DB::transaction(
                function () use ($existingRequest, $requestDetails, $requestSkills) {
                    $existingRequest->update($requestDetails);

                    RequestSkill::where("request_id", $existingRequest->id)->delete();

                    $requestSkills = $this->saveRequestSkills($requestSkills, $existingRequest);

                    $existingRequest->request_skills = formatRequestSkills($requestSkills);

                    $existingRequest->refresh();

                    return $existingRequest;
                }
            );
        }

        return $this->respond(Response::HTTP_OK, formatRequestForAPIResponse($response));
    }

    /**
     * Compares request skills to existing skills
     * to check whether there are new skills
     *
     * @param array $requestSkills - user updated skills
     * @param object $existingSkills - current skills unique to the request
     *
     * @return boolean
     */
    private function areNewSkillsAdded($currentSkills, $requestSkills)
    {
        $updatedSkills = call_user_func_array("array_merge", $requestSkills);

        $newSkills = array_diff($updatedSkills, $currentSkills);

        if ((count($updatedSkills) === count($currentSkills)) && empty($newSkills)) {
            return false;
        }

        return true;
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
     * Append the user being awaited
     *
     * @param array $requests - mentorship requests
     * @param String $userId - id of current user
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
