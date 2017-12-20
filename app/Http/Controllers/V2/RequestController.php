<?php
namespace App\Http\Controllers\V2;

use DB;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Models\UserSkill;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ConflictException;
use App\Models\Status;
use App\Models\Request as MentorshipRequest;
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
     * Gets Mentorship Requests
     *
     * @param Request $request - request  object
     *
     * @return Array $response - A formatted array of Mentorship Requests
     */
    public function getRequestsPool(Request $request)
    {
        // Get all request params
        $limit = $request->input("limit") ?
        intval($request->input("limit")) : 20;
        $params = $this->buildPoolFilterParams($request);
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
     * Build query params from request
     *
     * @param Request $request - request object
     *
     * @return Array $response - An associative array containing
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

        if ($currentUser->role !== "Admin" && $currentUser->uid !== $mentorshipRequest->mentee_id) {
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
        $requestTitle = $mentorshipRequest->title;
        $creationDate = $mentorshipRequest->created_at;
        $recipientSlackID = $mentorshipRequest->mentee->slack_id;
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
     * Accept a mentor who has shown interest in users mentorship request.
     *
     * @param Request $request - Request object
     * @param integer $mentorshipRequestId - mentorship request id with interested mentor
     *
     * @throws NotFoundException if mentorship request is not found
     *
     * @return Response object
     */
    public function acceptInterestedMentor(Request $request, $mentorshipRequestId)
    {
        $mentorshipRequest = MentorshipRequest::find($mentorshipRequestId);

        $this->validateBeforeAcceptOrRejectMentor($request, $mentorshipRequest);

        $interestedMentorId = $request->get("mentorId");

        $mentorshipRequest->mentor_id = $interestedMentorId;
        $mentorshipRequest->match_date = Carbon::now();
        $mentorshipRequest->status_id = Status::MATCHED;
        $mentorshipRequest->save();

        return $this->respond(Response::HTTP_OK, $mentorshipRequest);
    }

    /**
     * Reject a mentor who has shown interest in user's mentorship request
     *
     * @param Request $request - Request object
     * @param integer $mentorshipRequestId - mentorship request id with interested mentor
     *
     * @throws NotFoundException if mentorship request is not found
     *
     * @return Response object
     */
    public function rejectInterestedMentor(Request $request, $mentorshipRequestId)
    {
        $mentorshipRequest = MentorshipRequest::find($mentorshipRequestId);

        $this->validateBeforeAcceptOrRejectMentor($request, $mentorshipRequest);

        $allInterestedMentors = $mentorshipRequest->interested ?? [];
        $interestedMentorId = $request->get("mentorId");

        $interestedMentorIndex = array_search($interestedMentorId, $allInterestedMentors);
        array_splice($allInterestedMentors, $interestedMentorIndex, 1);
        $mentorshipRequest->interested =
            count($allInterestedMentors) > 0 ? $allInterestedMentors : null;
        $mentorshipRequest->save();

        return $this->respond(Response::HTTP_OK, $mentorshipRequest);
    }

    /**
     * Check validity of request in accept and reject interested mentor functions, if not
     * throw Exception
     *
     * @param Request $request - Request object
     * @param MentorshipRequest $mentorshipRequest - A mentorship request object
     *
     * @throws BadRequestException - if mentorship request is not open
     * @throws AccessDeniedException - if user does not own the mentorship request
     * @throws NotFoundException - if interested mentor given is not an interested mentor
     *
     * @return void
     */
    private function validateBeforeAcceptOrRejectMentor(Request $request, MentorshipRequest $mentorshipRequest)
    {
        $this->validate($request, MentorshipRequest::$acceptOrRejectMentorRules);

        if (!($mentorshipRequest)) {
            throw new NotFoundException("The mentorship request was not found");
        }

        if (!$mentorshipRequest->status_id === Status::OPEN) {
            throw new BadRequestException("Operation can only be performed on open requests");
        }

        $currentUser = $request->user();
        if ($currentUser->uid !== $mentorshipRequest->mentee_id) {
            throw new AccessDeniedException("You do not have permission to perform this operation");
        }

        $interestedMentorId = $request->get("mentorId");
        $allInterestedMentors = $mentorshipRequest->interested ?? [];
        if (!(in_array($interestedMentorId, $allInterestedMentors))) {
            throw new NotFoundException("The fellow is not an interested mentor");
        }
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
