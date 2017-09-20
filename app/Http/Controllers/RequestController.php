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
use App\Models\UserNotification;
use App\Models\Notification;
use App\Models\Request as MentorshipRequest;
use App\Repositories\SlackUsersRepository;
use App\Utility\SlackUtility;

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

    protected $ais_client;
    protected $slack_utility;
    protected $slack_repository;

    public function __construct(
        AISClient $ais_client,
        SlackUtility $slack_utility,
        SlackUsersRepository $slack_repository
    ) {
        $this->ais_client = $ais_client;
        $this->slack_utility = $slack_utility;
        $this->slack_repository = $slack_repository;
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
        $user_id = $request->user()->uid;
        $limit = intval($request->input("limit")) ?
            intval($request->input("limit")) : 20;
        $search_query = $this->getRequestParams($request, "q");

        // Add request params to the params array
        if (trim($search_query)) {
            $params["search_query"] = $search_query;
        }
        if ($this->getRequestParams($request, "mentee")) {
            $params["mentee_id"] = $user_id;
        }
        if ($this->getRequestParams($request, "mentor")) {
            $params["mentor_id"] = $user_id;
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
        $mentorship_requests  = MentorshipRequest::buildQuery($params)
                ->orderBy("created_at", "desc")
                ->paginate($limit);
            $response["pagination"] = [
                "totalCount" => $mentorship_requests->total(),
                "pageSize" => $mentorship_requests->perPage()
            ];
            // transform the result objects into API ready responses
            $transformed_requests = $this->transformRequestData($mentorship_requests);
            $response["requests"] = $transformed_requests;
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
        $mentorship_request = self::MODEL;

        $this->validate($request, MentorshipRequest::$rules);
        $user = $request->user();

        // update the user table with the mentee details
        $this->updateUserTable($user->uid, $user->email);

        $user_array = ["mentee_id" => $user->uid, "status_id" => Status::OPEN];
        $new_record = $this->filterRequest($request->all());
        $new_record = array_merge($new_record, $user_array);
        $created_request = $mentorship_request::create($new_record);

        $primary = $request->all()["primary"];
        $secondary = $request->all()["secondary"];
        $this->mapRequestToSkill($created_request->id, $primary, $secondary);

        /* find all mentors matching request we need to send them emails. however the
        the secondary field can sometimes be empty so no need to merge */
        $all_skills = $secondary ? array_merge($primary, $secondary) : $primary;
        $user_info = UserSkill::wherein('skill_id', $all_skills)->select('user_id')->get()->toArray();
        $mentor_ids = [];

        if ($user_info) {
            $mentor_ids = array_map(
                function ($user) {
                    return $user["user_id"];
                },
                $user_info
            );
        }

        /* we have a list of userids to send emails to, some of the user ids are duplicated
        because one person might have more than one skill matched */
        $mentor_ids = array_unique($mentor_ids);
        $request_url = $this->getClientBaseUrl().'/requests/'.$created_request->id;
        $bulk_email_addresses = [];
        $app_environment = getenv('APP_ENV');

        $slack_message = "*New Mentorship Request*".
            "\n*Title:* {$created_request->title}".
            "\n*Link:* {$request_url}";

        $slack_channel = Config::get("slack.{$app_environment}.new_request_channels");

        $this->slack_utility->sendMessage($slack_channel, $slack_message);

        try {
            // get email address of all the people to send the email to
            foreach ($mentor_ids as $mentor_id) {
                $mentor_details = $this->ais_client->getUserById($mentor_id);
                array_push($bulk_email_addresses, $mentor_details["email"]);
            }

            $email_content = [
                "content" => "You might be interested in this mentorship request.
                You can view the details of the request here {$request_url}",
                "title" => "Matching mentorship request"
            ];

            // send the email to the first person and bcc everyone else
            if (sizeof($bulk_email_addresses) > 0) {
                $this->sendEmail(
                    $email_content,
                    $bulk_email_addresses[0],
                    array_slice($bulk_email_addresses, 1)
                );
            }
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        return $this->respond(Response::HTTP_CREATED, $created_request);
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
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            $current_user = $request->user();

            if ($current_user->uid !== $mentorship_request->mentee_id) {
                throw new AccessDeniedException("You don't have permission to edit the mentorship request", 1);
            }
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        }

        $new_record = $this->filterRequest($request->all());
        $mentorship_request->fill($new_record)->save();

        if ($request->primary || $request->secondary) {
            $this->mapRequestToSkill(
                $id,
                $request->primary,
                $request->secondary
            );
        }

        return $this->respond(Response::HTTP_OK, $mentorship_request);
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
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            $current_user = $request->user();

            if (!$current_user) {
                throw new AccessDeniedException("You don't have permission to edit the mentorship request", 1);
            }

            // check that the mentee is not the one interested
            if (in_array($mentorship_request->mentee_id, $request->interested)) {
                throw new BadRequestException("You cannot indicate interest in your own mentorship request", 1);
            }

            // update the mentorship request model with new interested mentor
            $interested = $mentorship_request->interested;
            if ($interested === null) {
                $interested = [];
            }

            $request->interested = array_unique(array_merge($interested, $request->interested));
            $mentorship_request->interested = $request->interested;
            $mentorship_request->save();

            $mentor_name = $current_user->name;

            $mentee_id = $mentorship_request->mentee_id;
            $request_url = $this->getClientBaseUrl()."/requests/{$id}";

            // get user details from FIS and send email
            $mentee_details = $this->ais_client->getUserById($mentee_id);
            $mentee_name = $mentee_details["name"];
            $to_address = $mentee_details["email"];
            $email_content = [
                "content" => "{$mentor_name} has indicated interest in mentoring you.
                You can view the details of the request here {$request_url}",
                "title" => "Hello {$mentee_name}"
            ];

            /* send notification on interested mentors to
            users selected notification channels
             */
            $user_setting = UserNotification::getUserSettingById(
                $mentee_id,
                Notification::INDICATES_INTEREST
            );

            if ($user_setting['email']) {
                $this->sendEmail($email_content, $to_address);
            }

            if ($user_setting['slack']) {
                /* Send a slack notification to a mentee
                when a mentor shows interest in their request
                */
                $user = User::select('slack_id')
                    ->where('user_id', $mentee_id)
                    ->first();
                $message = "*{$mentor_name}* has indicated interest in mentoring you.\n"
                    ."View details of the request: {$request_url}";

                $this->slack_utility->sendMessage([$user->slack_id], $message);
            }

            return $this->respond(Response::HTTP_OK, $mentorship_request);
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
     * @param GoogleCalendarClient $google_calendar
     * @param integer $id Unique ID of the mentorship request
     * @return \Illuminate\Http\JsonResponse
     * @throws NotFoundException
     */
    public function updateMentor(
        Request $request,
        GoogleCalendarClient $google_calendar,
        $id
    ) {
        $this->validate($request, MentorshipRequest::$mentor_update_rules);

        $request->match_date = Date('Y-m-d H:i:s', $request->match_date);

        try {
            $mentorship_request = MentorshipRequest::with('requestSkills')->findOrFail(intval($id));
            $current_user = $request->user();
            $requestSkills = $mentorship_request->requestSkills()->get();
            foreach ($requestSkills as $skill) {
                UserSkill::firstOrCreate(
                    ["skill_id" => $skill->skill_id, "user_id" => $request->mentor_id]
                );
            }

            if ($current_user->uid !== $mentorship_request->mentee_id) {
                throw new AccessDeniedException("You don't have permission to edit the mentorship request", 1);
            }

            // update mentor for mentorship request
            $mentorship_request->mentor_id = $request->mentor_id;
            $mentorship_request->match_date = $request->match_date;
            $mentorship_request->status_id = Status::MATCHED;
            $mentorship_request->save();

            // get mentee name and request url and add to email content
            $mentee_name = $request->mentee_name;
            $request_url = $this->getClientBaseUrl().'/requests/'.$id;
            $content = [
                "content" => "{$mentee_name} selected you as a mentor
                You can view the details of the request here {$request_url}",
                "title" => 'Mentorship interest accepted'
            ];

            // get mentor id and send email content
            $user_setting = UserNotification::getUserSettingById(
                $request->mentor_id,
                Notification::SELECTED_AS_MENTOR
            );
            $body = $this->ais_client->getUserById($request->mentor_id);

            $mentor_email = $body["email"];
            if ($user_setting['email']) {
                $this->sendEmail($content, $mentor_email);
            }

            //Post event to Google Calendar
            $mentee_email = $current_user->email;
            $event_details = $this->getEventDetails(
                $mentee_email,
                $mentor_email,
                $mentorship_request
            );

            //Post event to Google Calendar
            $google_calendar->createEvent($event_details);

            // Send the mentor a slack message when notified
            if ($user_setting['slack']) {
                $mentee_id = $request->input('mentor_id');
                $user = User::select('slack_id')
                    ->where('user_id', $mentee_id)
                    ->first();
                $message = "{$mentee_name} selected you as a mentor
                \n"."View details of the request: {$request_url}";
                $this->slack_utility->sendMessage([$user->slack_id], $message);
            }
            
            return $this->respond(Response::HTTP_OK, $mentorship_request);
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        } catch (\Google_Service_Exception $exception) {
            $error = json_decode($exception->getMessage())->{"error"};

            $message = $error->{"message"};
            $status_code = $error->{"code"};

            return $this->respond($status_code, ["message" => $message]);
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
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            $current_user = $request->user();

            if ($current_user->uid !== $mentorship_request->mentee_id) {
                throw new UnauthorizedException("You don't have permission to cancel this mentorship request", 1);
            }

            $mentorship_request->status_id = Status::CANCELLED;
            $mentorship_request->save();

            $this->respond(Response::HTTP_OK, ["message" => "Request Cancelled"]);
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        } catch (UnauthorizedException $exception) {
            return $this->respond(Response::HTTP_FORBIDDEN, ["message" => $exception->getMessage()]);
        }

        return $this->respond(Response::HTTP_OK, $mentorship_request);
    }

    /**
     * Maps the skills in the request body by type and
     * saves them in the request_skills table
     *
     * @param integer $request_id the id of the request
     * @param string $primary type of skill to map
     * @param string $secondary type of skill to map
     * @return void
     */
    private function mapRequestToSkill($request_id, $primary, $secondary)
    {
        // Delete all skills from the request_skills table that match the given
        // $request_id before performing another insert
        RequestSkill::where('request_id', $request_id)->delete();

        if ($primary) {
            foreach ($primary as $skill) {
                RequestSkill::create(
                    [
                    "request_id" => $request_id,
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
                    "request_id" => $request_id,
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
     * @return  object
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
     *
     * @return object
     */
    private function formatRequestData($result)
    {
        $formatted_result = (object) [
            "id" => $result->id,
            "mentee_id" => $result->mentee_id,
            "mentee_email" => $result->user->email ?? '',
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
        return $formatted_result;
    }

    /**
     * Format Request Skills
     * Filter the result from skills table and add to the skills array
     *
     * @param array $request_skills - the request skills
     *
     * @return array $skills
     */
    private function formatRequestSkills($request_skills)
    {
        $skills = [];

        foreach ($request_skills as $request) {
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
     * @param string $time
     * @return mixed null|string
     */
    private function formatTime($time)
    {
        return $time === null ? null : date('Y-m-d H:i:s', $time->getTimestamp());
    }

    /**
     * Method that transforms the mentorship requests into a response object
     *
     * @param array $mentorship_requests - the request object
     *
     * @return array
     */
    public function transformRequestData($mentorship_requests)
    {
        $transformed_requests = [];

        foreach ($mentorship_requests as $mentorship_request) {
            $mentorship_request->request_skills = $mentorship_request->requestSkills;

            foreach ($mentorship_request->request_skills as $skill) {
                $skill = $skill->skill;
            }
            $transformed_request = $this->formatRequestData($mentorship_request);
            array_push($transformed_requests, $transformed_request);
        }

        return $transformed_requests;
    }

    /**
     * Generic send email method
     *
     * @param array $email_content the email content to be sent
     * @param string $to_address email address the email is supposed to go to
     * @param boolean $bcc optional argument, recipients of the email
     * @param string $blade_template email template to be used
     */
    private function sendEmail($email_content, $to_address, $bcc = false, $blade_template = 'email')
    {
        try {
            Mail::send(
                ['html' => $blade_template],
                $email_content,
                function ($msg) use ($to_address, $bcc) {
                    $msg->subject('Lenken Notification');
                    $msg->to([$to_address]);
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
     * @param string $user_id
     * @param        $user_email
     */
    public function updateUserTable($user_id, $user_email)
    {
        // fetch the user's slack details from the repository
        $slack_user = $this->slack_repository->getByEmail($user_email);

        $user_details = [
            "email" => $user_email,
            "slack_id" => $slack_user ? $slack_user->id : null
        ];

        // if the user's user_id is not in the table, create a new user
        User::updateOrCreate(
            ["user_id" => $user_id],
            $user_details
        );
    }

    /**
     * Add request event to Google Calendar
     *
     * @param string $mentee_email email address of the mentee
     * @param string $mentor_email email address of the mentor
     * @param object $mentorship_request mentorship request made
     * @return array $event_details formatted Event details for google calendar
     */

    private function getEventDetails($mentee_email, $mentor_email, $mentorship_request)
    {
        $match_date = $mentorship_request->match_date;
        $session_start_time = $mentorship_request->pairing["start_time"] . ":00";
        $session_end_time = $mentorship_request->pairing["end_time"] . ":00";
        $duration = $mentorship_request->duration;

        $timezone = formatCalendarTimezone($mentorship_request->pairing["timezone"]);

        //Format start date and end date to 'Y-m-sTH:m:s' format
        $event_start_date = calculateEventStartDate(
            $mentorship_request->pairing["days"],
            $match_date
        );

        $daily_start_time = formatCalendarDate(
            $event_start_date,
            $session_start_time
        );

        $daily_end_time = formatCalendarDate(
            $event_start_date,
            $session_end_time
        );

        $event_end_date = formatCalendarDate(
            $event_start_date,
            $session_end_time,
            $duration
        );

        $recursion_rule = getCalendarRecursionRule(
            $mentorship_request->pairing["days"],
            $event_end_date
        );

        //Prepare the event details
        $event_details = [
            "summary" => $mentorship_request->title,
            "description" => $mentorship_request->description,
            "start" => ["dateTime" => $daily_start_time, "timeZone" => $timezone,],
            "end" => ["dateTime" => $daily_end_time, "timeZone" => $timezone,],
            "recurrence" => [$recursion_rule],
            "attendees" => [
                ["email" => $mentor_email],
                ["email" => $mentee_email],
            ],
            "reminders" => [
                "useDefault" => false,
                "overrides" => [
                    ["method" => "email", "minutes" => 24 * 60],
                    ["method" => "popup", "minutes" => 10],
                ],
            ]
        ];

        return $event_details;
    }
    
    private function getRequestParams($request, $key)
    {
        return $request->input($key) ?? null;
    }
}
