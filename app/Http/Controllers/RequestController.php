<?php

namespace App\Http\Controllers;

use \Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Clients\AISClient;
use App\Clients\GoogleCalendarClient;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\BadRequestException;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\Status;
use App\Models\RequestSkill;
use App\Models\RequestCancellationReason;
use App\Models\UserNotification;
use App\Models\Notification;
use App\Models\Request as MentorshipRequest;
use App\Repositories\SlackUsersRepository;
use App\Utility\SlackUtility;
use App\Models\RequestExtension;

/**
 * Class RequestController
 *
 * @package App\Http\Controllers
 */

class RequestController extends Controller
{
    const MODEL = "App\Models\Request";
    const MODEL2 = "App\Models\RequestSkill";

    use RESTActions;

    protected $aisClient;
    protected $slackUtility;
    protected $slackRepository;

    public function __construct(
        AISClient $aisClient,
        SlackUtility $slackUtility,
        SlackUsersRepository $slackRepository
    ) {
        $this->aisClient = $aisClient;
        $this->slackUtility = $slackUtility;
        $this->slackRepository = $slackRepository;
    }

    /**
     * Gets all Mentorship Requests
     *
     * @param Request $request - request  object
     *
     * @return Response Object
     */
    public function all(Request $request)
    {
        // Get all request params
        $params = [];
        $user = $request->user();
        $userId = $request->user()->uid;
        $limit = intval($request->input("limit")) ?
            intval($request->input("limit")) : 20;
        $searchQuery = $this->getRequestParams($request, "q");

        // Add request params to the params array
        if (trim($searchQuery)) {
            $params["search_query"] = $searchQuery;
        }
        if ($this->getRequestParams($request, "mentee")) {
            $params["mentee_id"] = $userId;
        }
        if ($this->getRequestParams($request, "mentor")) {
            $params["mentor_id"] = $userId;
        }
        if ($status = $this->getRequestParams($request, "status")) {
            $params["status"] = explode(",", $status);
        }
        if ($period = $this->getRequestParams($request, "period")) {
            $params["date"] = $period;
        }
        if ($location = $this->getRequestParams($request, "location")) {
            $params["location"] = $location;
        }
        if ($skills = $this->getRequestParams($request, "skills")) {
            $params["skills"] = explode(",", $skills);
        }
        //update or create users in the users table
        $this->updateUserTable($user->uid, $user->email);
        $mentorshipRequests  = MentorshipRequest::buildQuery($params)
                ->orderBy("created_at", "desc")
                ->paginate($limit);
            $response["pagination"] = [
                "totalCount" => $mentorshipRequests->total(),
                "pageSize" => $mentorshipRequests->perPage()
            ];
            // transform the result objects into API ready responses
            $transformedRequests = $this->transformRequestData($mentorshipRequests);
            $response["requests"] = $transformedRequests;
            return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Gets a mentorship request by the request id
     *
     * @param integer id - $id - the request id
     *
     * @return Response object
     */
    public function get($id)
    {
        try {
            $result = MentorshipRequest::findOrFail(intval($id));
            $result->request_skills = $result->requestSkills;

            foreach ($result->request_skills as $skill) {
                $skill = $skill->skill;
            }

            $extension = RequestExtension::where("request_id", intval($id))
                ->first();

            $result->extension = $extension;

            $result = $this->formatRequestData($result);

            $response = [
                "data" => $result
            ];

            return $this->respond(Response::HTTP_OK, $response);
        } catch (ModelNotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => "The request was not found"]);
        }
    }

    /**
     * Creates a new Mentorship request and saves in the request table
     * Also saves the request skills in the request skills table
     *
     * @param Request $request - request object
     *
     * @return object Response object of created request
     */
    public function add(Request $request)
    {
        $mentorshipRequest = self::MODEL;

        $this->validate($request, MentorshipRequest::$rules);
        $user = $request->user();

        // update the user table with the mentee details
        $this->updateUserTable($user->uid, $user->email);

        $userArray = ["mentee_id" => $user->uid, "status_id" => Status::OPEN];
        $newRecord = $this->filterRequest($request->all());
        $newRecord = array_merge($newRecord, $userArray);
        $createdRequest = $mentorshipRequest::create($newRecord);

        $primary = $request->all()["primary"];
        $secondary = $request->all()["secondary"];
        $this->mapRequestToSkill($createdRequest->id, $primary, $secondary);

        /* find all mentors matching request we need to send them emails. however the
        the secondary field can sometimes be empty so no need to merge */
        $allSkills = $secondary ? array_merge($primary, $secondary) : $primary;
        $userInfo = UserSkill::wherein('skill_id', $allSkills)->select('user_id')->get()->toArray();
        $mentorIds = [];

        if ($userInfo) {
            $mentorIds = array_map(
                function ($user) {
                    return $user["user_id"];
                },
                $userInfo
            );
        }

        /* we have a list of userids to send emails to, some of the user ids are duplicated
        because one person might have more than one skill matched */
        $mentorIds = array_unique($mentorIds);
        /* remove mentee_id from an array of mentor_ids if present,
         * to avoid sending mentor matching requests to the mentee
         */
        if (($key = array_search($user->uid, $mentorIds)) !== false) {
            unset($mentorIds[$key]);
        }

        $requestUrl = $this->getClientBaseUrl().'/requests/'.$createdRequest->id;

        $this->sendNewMentorshipRequestNotification($createdRequest, $requestUrl);
        $this->sendMatchingSkillsNotification($mentorIds, $requestUrl);

        return $this->respond(Response::HTTP_CREATED, $createdRequest);
    }

    /**
     * Edit a mentorship request
     *
     * @param Request $request - request object
     * @param integer $id      - Unique ID of the mentorship request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AccessDeniedException | NotFoundException
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, MentorshipRequest::$rules);

        try {
            $mentorshipRequest = MentorshipRequest::findOrFail(intval($id));
            $currentUser = $request->user();

            if ($currentUser->uid !== $mentorshipRequest->mentee_id) {
                throw new AccessDeniedException("You don't have permission to edit the mentorship request", 1);
            }
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        }

        $newRecord = $this->filterRequest($request->all());
        $mentorshipRequest->fill($newRecord)->save();

        if ($request->primary || $request->secondary) {
            $this->mapRequestToSkill(
                $id,
                $request->primary,
                $request->secondary
            );
        }

        return $this->respond(Response::HTTP_OK, $mentorshipRequest);
    }

    /**
     * Edit a mentorship request interested field
     *
     *   Request $request
     *
     * @param Request $request - the request object
     * @param integer $id      - Unique ID of the mentorship request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AccessDeniedException | BadRequestException | NotFoundException
     */
    public function updateInterested(Request $request, $id)
    {
        $this->validate($request, MentorshipRequest::$mentee_rules);

        try {
            $mentorshipRequest = MentorshipRequest::findOrFail(intval($id));
            $currentUser = $request->user();

            if (!$currentUser) {
                throw new AccessDeniedException("You don't have permission to edit the mentorship request", 1);
            }

            // check that the mentee is not the one interested
            if (in_array($mentorshipRequest->mentee_id, $request->interested)) {
                throw new BadRequestException("You cannot indicate interest in your own mentorship request", 1);
            }

            // update the mentorship request model with new interested mentor
            $interested = $mentorshipRequest->interested;
            if ($interested === null) {
                $interested = [];
            }

            $request->interested = array_unique(array_merge($interested, $request->interested));
            $mentorshipRequest->interested = $request->interested;
            $mentorshipRequest->save();

            $mentorName = $currentUser->name;

            $menteeId = $mentorshipRequest->mentee_id;
            $requestUrl = $this->getClientBaseUrl()."/requests/{$id}";

            // get user details from FIS and send email
            $menteeDetails = $this->aisClient->getUserById($menteeId);
            $menteeName = $menteeDetails["name"];
            $toAddress = $menteeDetails["email"];
            $emailContent = [
                "content" => "{$mentorName} has indicated interest in mentoring you.
                You can view the details of the request here {$requestUrl}",
                "title" => "Hello {$menteeName}"
            ];

            /* send notification on interested mentors to
            users selected notification channels
             */
            $userSetting = UserNotification::getUserSettingById(
                $menteeId,
                Notification::INDICATES_INTEREST
            );

            if ($userSetting->email) {
                $this->sendEmail($emailContent, $toAddress);
            }

            if ($userSetting->slack) {
                /* Send a slack notification to a mentee
                when a mentor shows interest in their request
                */
                $user = User::select('slack_id')
                    ->where('user_id', $menteeId)
                    ->first();
                $message = "*{$mentorName}* has indicated interest in mentoring you.\n"
                    ."View details of the request: {$requestUrl}";

                $this->slackUtility->sendMessage([$user->slack_id], $message);
            }

            return $this->respond(Response::HTTP_OK, $mentorshipRequest);
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }
    }


    /**
     * Edit a mentorship request mentor_id field
     *
     * @param Request $request
     * @param GoogleCalendarClient $googleCalendar
     * @param integer $id Unique ID of the mentorship request
     * @return \Illuminate\Http\JsonResponse
     * @throws NotFoundException
     */
    public function updateMentor(
        Request $request,
        GoogleCalendarClient $googleCalendar,
        $id
    ) {
        $this->validate($request, MentorshipRequest::$mentor_update_rules);

        $request->match_date = Date('Y-m-d H:i:s', $request->match_date);

        try {
            $mentorshipRequest = MentorshipRequest::with('requestSkills')->findOrFail(intval($id));
            $currentUser = $request->user();
            $requestSkills = $mentorshipRequest->requestSkills()->get();
            foreach ($requestSkills as $skill) {
                UserSkill::firstOrCreate(
                    ["skill_id" => $skill->skill_id, "user_id" => $request->mentor_id]
                );
            }

            if ($currentUser->uid !== $mentorshipRequest->mentee_id) {
                throw new AccessDeniedException("You don't have permission to edit the mentorship request", 1);
            }

            // update mentor for mentorship request
            $mentorshipRequest->mentor_id = $request->mentor_id;
            $mentorshipRequest->match_date = $request->match_date;
            $mentorshipRequest->status_id = Status::MATCHED;
            $mentorshipRequest->save();

            // get mentee name and request url and add to email content
            $menteeName = $request->mentee_name;
            $requestUrl = $this->getClientBaseUrl().'/requests/'.$id;
            $content = [
                "content" => "{$menteeName} selected you as a mentor
                You can view the details of the request here {$requestUrl}",
                "title" => 'Mentorship interest accepted'
            ];

            // get mentor id and send email content
            $userSetting = UserNotification::getUserSettingById(
                $request->mentor_id,
                Notification::SELECTED_AS_MENTOR
            );
            $body = $this->aisClient->getUserById($request->mentor_id);

            $mentorEmail = $body["email"];
            if ($userSetting->email) {
                $this->sendEmail($content, $mentorEmail);
            }

            //Post event to Google Calendar
            $menteeEmail = $currentUser->email;
            $eventDetails = $this->getEventDetails(
                $menteeEmail,
                $mentorEmail,
                $mentorshipRequest
            );

            //Post event to Google Calendar
            $googleCalendar->createEvent($eventDetails);

            // Send the mentor a slack message when notified
            if ($userSetting->slack) {
                $menteeId = $request->input('mentor_id');
                $user = User::select('slack_id')
                    ->where('user_id', $menteeId)
                    ->first();
                $message = "{$menteeName} selected you as a mentor
                \n"."View details of the request: {$requestUrl}";
                $this->slackUtility->sendMessage([$user->slack_id], $message);
            }

            return $this->respond(Response::HTTP_OK, $mentorshipRequest);
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        } catch (\Google_Service_Exception $exception) {
            $error = json_decode($exception->getMessage())->{"error"};

            $message = $error->{"message"};
            $statusCode = $error->{"code"};

            return $this->respond($statusCode, ["message" => $message]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }
    }

    /**
     * Set a request status to cancelled
     *
     * @param Request $request
     * @param  integer $id Unique ID used to identify the request
     * @return \Illuminate\Http\JsonResponse
     * @throws NotFoundException
     */
    public function cancelRequest(Request $request, $id)
    {
        try {
            $mentorshipRequest = MentorshipRequest::findOrFail(intval($id));
            $currentUser = $request->user();

            if ($currentUser->role !== "Admin" && $currentUser->uid !== $mentorshipRequest->mentee_id) {
                throw new UnauthorizedException("You don't have permission to cancel this mentorship request", 1);
            }

            $mentorshipRequest->status_id = Status::CANCELLED;
            $mentorshipRequest->save();

            $reason = ucfirst($request->input("reason"));
            RequestCancellationReason::create(
                [
                    "request_id" => $mentorshipRequest->id,
                    "user_id" => $currentUser->uid,
                    "reason" => $reason
                ]
            );

            $this->sendCancellationNotification($mentorshipRequest, $reason);

            return $this->respond(Response::HTTP_OK, ["message" => "Request Cancelled."]);

        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        } catch (UnauthorizedException $exception) {
            return $this->respond(Response::HTTP_FORBIDDEN, ["message" => $exception->getMessage()]);
        }
    }
    
    /**
     * Send slack notification for cancelled request
     *
     * @param Object $mentorshipRequest Cancelled request
     *
     * @return void
     */
    private function sendCancellationNotification($mentorshipRequest, $reason)
    {
        $title = $mentorshipRequest->title;
        $createdAt = $mentorshipRequest->created_at;
        $slackId = $mentorshipRequest->mentee->slack_id;

        $slackMessage = "Your *mentorship request* `{$title}`
            opened on `{$createdAt}` has been cancelled \n*REASON:* `{$reason}`.";
        $this->slackUtility->sendMessage([$slackId], $slackMessage);
    }
    
    /**
     * Maps the skills in the request body by type and
     * saves them in the request_skills table
     *
     * @param integer $requestId the id of the request
     * @param string $primary type of skill to map
     * @param string $secondary type of skill to map
     * @return void
     */
    private function mapRequestToSkill($requestId, $primary, $secondary)
    {
        // Delete all skills from the request_skills table that match the given
        // $requestId before performing another insert
        RequestSkill::where('request_id', $requestId)->delete();

        if ($primary) {
            foreach ($primary as $skill) {
                RequestSkill::create(
                    [
                    "request_id" => $requestId,
                    "skill_id" => $skill,
                    "type" => "primary"
                    ]
                );
            }
        }

        if ($secondary) {
            foreach ($secondary as $skill) {
                RequestSkill::create(
                    [
                    "request_id" => $requestId,
                    "skill_id" => $skill,
                    "type" => "secondary"
                    ]
                );
            }
        }
    }

    /**
     * Filter incoming request body to remove object property
     * containing primary and secondary skills
     *
     * @param  object $request
     * @return object
     */
    private function filterRequest($request)
    {
        return array_filter(
            $request,
            function ($key) {
                return $key !== 'primary' && $key !== 'secondary';
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Format request data
     * extracts returned request queries to match data on client side
     *
     * @param object $result - the result object
     * @param object $extensionRequest the request extension object
     *
     * @return object
     */
    private function formatRequestData($result)
    {
        $formattedResult = (object) [
            "id" => $result->id,
            "mentee_id" => $result->mentee_id,
            "mentee_email" => $result->mentee->email ?? '',
            "mentor_id" => $result->mentor_id,
            "mentor_email" => $result->mentor->email ?? '',
            "title" => $result->title,
            "description" => $result->description,
            "interested" => $result->interested,
            "status_id" => $result->status_id,
            "match_date" => $result->match_date,
            "duration" => $result->duration,
            "pairing" => $result->pairing,
            "request_skills" => $this->formatRequestSkills($result->request_skills),
            "status" => $result->status->name,
            "created_at" => $this->formatTime($result->created_at),
            "updated_at" => $this->formatTime($result->updated_at)
        ];
        if ($result->extension) {
            $formattedResult->extension = $result->extension;
        }

        return $formattedResult;
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

        foreach ($requestSkills as $request) {
            $result = (object) [
                "id" => $request->skill_id,
                "type" => $request->type,
                "name" => $request->skill->name
            ];
            array_push($skills, $result);
        }

        return $skills;
    }

    /**
     * Format time
     * checks if the given time is null and returns null else it returns the time in the date format
     *
     * @param  string $time
     * @return mixed null|string
     */
    private function formatTime($time)
    {
        return $time === null ? null : date('Y-m-d H:i:s', $time->getTimestamp());
    }

    /**
     * Method that transforms the mentorship requests into a response object
     *
     * @param array $mentorshipRequests - the request object
     *
     * @return array
     */
    public function transformRequestData($mentorshipRequests)
    {
        $transformedRequests = [];

        foreach ($mentorshipRequests as $mentorshipRequest) {
            $mentorshipRequest->request_skills = $mentorshipRequest->requestSkills;

            foreach ($mentorshipRequest->request_skills as $skill) {
                $skill = $skill->skill;
            }
            $transformedRequest = $this->formatRequestData($mentorshipRequest);
            array_push($transformedRequests, $transformedRequest);
        }

        return $transformedRequests;
    }

    /**
     * Generic send email method
     *
     * @param array $emailContent the email content to be sent
     * @param string $toAddress email address the email is supposed to go to
     * @param boolean $bcc optional argument, recipients of the email
     * @param string $bladeTemplate email template to be used
     */
    private function sendEmail($emailContent, $toAddress, $bcc = false, $bladeTemplate = 'email')
    {
        try {
            Mail::send(
                ['html' => $bladeTemplate],
                $emailContent,
                function ($msg) use ($toAddress, $bcc) {
                    $msg->subject('Lenken Notification');
                    $msg->to([$toAddress]);
                    $msg->from(['lenken-tech@andela.com']);

                    if ($bcc) {
                        $msg->bcc($bcc);
                    }
                }
            );
        } catch (Exception $e) {
            /*
             * we might not want to do anything as the request was successful,
             * just that we could not send an email
             */
        }
    }

    /**
     * Returns the client base url which we use to give redirect links in the emails
     *
     * @return string the base url
     */
    private function getClientBaseUrl()
    {
        return getenv('LENKEN_FRONTEND_BASE_URL');
    }

    /**
     * Updates the users table with unique user details
     * each time a new request is made. In the case when
     * user details are already there the method terminates
     * to allow program flow without any errors
     *
     * @param string $userId
     * @param        $userEmail
     */
    public function updateUserTable($userId, $userEmail)
    {
        // fetch the user's slack details from the repository
        $slackUser = $this->slackRepository->getByEmail($userEmail);

        $userDetails = [
            "email" => $userEmail,
            "slack_id" => $slackUser ? $slackUser->id : null
        ];

        // if the user's userId is not in the table, create a new user
        User::updateOrCreate(
            ["user_id" => $userId],
            $userDetails
        );
    }

    /**
     * Add request event to Google Calendar
     *
     * @param string $menteeEmail email address of the mentee
     * @param string $mentorEmail email address of the mentor
     * @param object $mentorshipRequest mentorship request made
     * @return array $eventDetails formatted Event details for google calendar
     */

    private function getEventDetails($menteeEmail, $mentorEmail, $mentorshipRequest)
    {
        $matchDate = $mentorshipRequest->match_date;
        $sessionStartTime = $mentorshipRequest->pairing["start_time"] . ":00";
        $sessionEndTime = $mentorshipRequest->pairing["end_time"] . ":00";
        $duration = $mentorshipRequest->duration;

        $timezone = formatCalendarTimezone($mentorshipRequest->pairing["timezone"]);

        //Format start date and end date to 'Y-m-sTH:m:s' format
        $eventStartDate = calculateEventStartDate(
            $mentorshipRequest->pairing["days"],
            $matchDate
        );

        $dailyStartTime = formatCalendarDate(
            $eventStartDate,
            $sessionStartTime
        );

        $dailyEndTime = formatCalendarDate(
            $eventStartDate,
            $sessionEndTime
        );

        $eventEndDate = formatCalendarDate(
            $eventStartDate,
            $sessionEndTime,
            $duration
        );

        $recursionRule = getCalendarRecursionRule(
            $mentorshipRequest->pairing["days"],
            $eventEndDate
        );

        //Prepare the event details
        $eventDetails = [
            "summary" => $mentorshipRequest->title,
            "description" => $mentorshipRequest->description,
            "start" => ["dateTime" => $dailyStartTime, "timeZone" => $timezone,],
            "end" => ["dateTime" => $dailyEndTime, "timeZone" => $timezone,],
            "recurrence" => [$recursionRule],
            "attendees" => [
                ["email" => $mentorEmail],
                ["email" => $menteeEmail],
            ],
            "reminders" => [
                "useDefault" => false,
                "overrides" => [
                    ["method" => "email", "minutes" => 24 * 60],
                    ["method" => "popup", "minutes" => 10],
                ],
            ]
        ];

        return $eventDetails;
    }

    /**
     * Create a request to extend the mentorship period
     *
     * @param Request $request the request object
     * @param integer $id      the request id
     *
     * @return mixed the http response
     */
    public function requestExtension(Request $request, $id)
    {
        try {
            $mentorshipRequest = MentorshipRequest::find($id);
            if (!$mentorshipRequest) {
                throw new NotFoundException(
                    "Request not found"
                );
            }

            $currentUser = $request->user();

            if ($currentUser->uid !== $mentorshipRequest->mentee_id) {
                throw new AccessDeniedException(
                    "You don't have permission to request an extension for this mentorship"
                );
            }
            RequestExtension::updateOrCreate(
                ["request_id" => $id],
                [
                    "approved" => null
                ]
            );
            $message = "A request for extension of a mentorship period " .
                "has been made by your mentee, Please review it here";
            $slackId = $mentorshipRequest->mentor->slack_id;

            if ($slackId) {
                $baseUrl = $this->getClientBaseUrl();
                $this->slackUtility->sendMessage(
                    [$slackId],
                    "$message $baseUrl/requests/$id"
                );
            }

            return $this->respond(
                Response::HTTP_CREATED,
                ["message" => "Your request was submitted successfully"]
            );
        } catch (Exception $e) {
            return ["message" => $e->getMessage()];
        }
    }

    /**
     * Extend mentorship request period
     *
     * @param Request $request the request object
     * @param integer $id      the request id
     *
     * @return mixed the http response
     */
    public function approveExtensionRequest(Request $request, $id)
    {
        try {
            $mentorshipRequest = MentorshipRequest::find($id);

            if (!$mentorshipRequest) {
                throw new NotFoundException(
                    "Request not found"
                );
            }
            $currentUser = $request->user();

            if ($currentUser->uid !== $mentorshipRequest->mentor_id) {
                throw new AccessDeniedException(
                    "You don't have permission to approve an extension of this mentorship"
                );
            }
            $extension = RequestExtension::where("request_id", $id)
                ->first();

            if ($extension) {
                $mentorshipRequest->duration += 0.5;
                $mentorshipRequest->save();

                $extension->approved = true;
                $extension->save();

                $message = "Your request for extension of a mentorship period " .
                    "has been approved, Please review it here";

                $slackId = $mentorshipRequest->mentee->slack_id;
                if ($slackId) {
                    $baseUrl = $this->getClientBaseUrl();
                    $this->slackUtility->sendMessage(
                        [$slackId],
                        "$message $baseUrl/requests/$id"
                    );
                }

                return $this->respond(
                    Response::HTTP_OK,
                    ["message" => "Mentorship period was extended successfully"]
                );
            }
        } catch (Exception $e) {
            return ["message" => $e->getMessage()];
        }
    }

    /**
     * Reject mentorship extension request
     *
     * @param Request $request the request object
     * @param integer $id      the request id
     *
     * @return mixed the http response
     */
    public function rejectExtensionRequest(Request $request, $id)
    {
        try {
            $mentorshipRequest = MentorshipRequest::find($id);

            if (!$mentorshipRequest) {
                throw new NotFoundException(
                    "Request not found"
                );
            }
            $currentUser = $request->user();

            if ($currentUser->uid !== $mentorshipRequest->mentor_id) {
                throw new AccessDeniedException(
                    "You don't have permission to reject an extension of this mentorship"
                );
            }
            $extension = RequestExtension::where("request_id", $id)
                ->first();

            if ($extension) {
                $extension->approved = false;
                $extension->save();

                $message = "Your request for extension of a mentorship period " .
                    "has been rejected, Please review it here";
                $slackId = $mentorshipRequest->mentee->slack_id;
                if ($slackId) {
                    $baseUrl = $this->getClientBaseUrl();
                    $this->slackUtility->sendMessage(
                        [$slackId],
                        "$message $baseUrl/requests/$id"
                    );
                }

                return $this->respond(
                    Response::HTTP_OK,
                    ["message"
                    => "Mentorship extension request was rejected successfully"
                    ]
                );
            }
        } catch (Exception $e) {
            return ["message" => $e->getMessage()];
        }
    }

    /**
     * Send matching mentorship request to mentors
     *
     * @param $mentorIds - IDs of all mentors with matching skills
     * @param $requestUrl - URL to the new request
     */
    private function sendMatchingSkillsNotification($mentorIds, $requestUrl)
    {
        $potentialMentorIdsForEmail = [];
        $potentialMentorIdsForSlack = [];

        $usersSetting = UserNotification::getUsersSettingById(
            $mentorIds,
            Notification::REQUESTS_MATCHING_SKILLS
        );

        foreach ($usersSetting as $userSetting) {
            if ($userSetting['email']) {
                $potentialMentorIdsForEmail[] = $userSetting['user_id'];
            }

            if ($userSetting['slack']) {
                $potentialMentorIdsForSlack[] = $userSetting["user_id"];
            }
        }

        if (count($potentialMentorIdsForEmail) > 0) {
            $potentialMentorEmails = User::select('email')
                ->whereIn('user_id', $potentialMentorIdsForEmail)
                ->get();
            $potentialMentorEmails
                = array_flatten(json_decode($potentialMentorEmails, true));

            if (count($potentialMentorEmails) > 0) {
                $emailContent = [
                    "content" => "You might be interested in this mentorship request.
                            You can view the details of the request here " .
                        "{$requestUrl}?referrer=email",
                    "title" => "Matching mentorship request"
                ];
                // send the email to the first person and bcc everyone else
                $this->sendEmail(
                    $emailContent,
                    $potentialMentorEmails[0],
                    array_slice($potentialMentorEmails, 1)
                );
            }
        }

        if (count($potentialMentorIdsForSlack) > 0) {
            $potentialMentorSlackIds = User::select('slack_id')
                ->whereIn('user_id', $potentialMentorIdsForSlack)
                ->get();
            $potentialMentorSlackIds
                = array_flatten(json_decode($potentialMentorSlackIds, true));

            if (count($potentialMentorSlackIds) > 0) {
                $message = "*Matching mentorship request*\n".
                    "You might be interested in this mentorship request.\n".
                    "View details of the request here: {$requestUrl}?referrer=slack";
                $this->slackUtility->sendMessage($potentialMentorSlackIds, $message);
            }
        }
    }

    /**
     * Send the slack notification for new requests
     *
     * @param MentorshipRequest $createdRequest - the newly created request
     * @param string $requestUrl - the URL to the request on the frontend
     *
     * @return void
     */
    private function sendNewMentorshipRequestNotification($createdRequest, $requestUrl)
    {
        $slackMessage = "*New Mentorship Request*".
            "\n*Title:* {$createdRequest->title}".
            "\n*Link:* {$requestUrl}?referrer=slack";

        $appEnvironment = getenv('APP_ENV');
        $slackChannel = Config::get("slack.{$appEnvironment}.new_request_channels");

        $this->slackUtility->sendMessage($slackChannel, $slackMessage);
    }
}
