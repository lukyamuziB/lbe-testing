<?php
namespace App\Http\Controllers;

use App\User;
use App\UserSkill;
use App\Status;
use App\Skill;
use App\RequestSkill;
use App\Request as MentorshipRequest;
use App\Utility\SlackUtility as Slack;
use App\Exceptions\Exception;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AccessDeniedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Lcobucci\JWT\Parser;
use GuzzleHttp\Client;

/**
 * Class RequestController
 * @package App\Http\Controllers
 */
class RequestController extends Controller
{
    const MODEL = "App\Request";
    const MODEL2 = "App\RequestSkill";

    use RESTActions;

    /**
     * Gets all Mentorship Requests
     *
     * @param Request $request - request  object
     * @return Response Object
     */
    public function all(Request $request)
    {
        // generic collection to hold requests to be sent as response
        $mentorship_requests = [];

        // build all where clauses based off of query params (location & time)
        if ($request->input('self')) {
            $mentorship_requests = $this->getMenteeRequests($request->user()->uid, $request);
        } elseif ($request->input('mentor')) {
            $mentorship_requests = $this->getAllRequestByMentorSkills($request->user()->uid);
        } else {
            $mentorship_requests = MentorshipRequest::buildWhereClause($request)
                ->orderBy('created_at', 'desc')->get();
        }

        // transform the result objects into API ready responses
        $transformed_requests = $this->transformRequestData($mentorship_requests);
        $response = [
            "data" => $transformed_requests
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Gets a mentorship request by the request id
     *
     * @param integer $id
     * @return Response object
     */
    public function get($id)
    {
        $result = MentorshipRequest::find($id);
        $result->request_skills = $result->requestSkills;

        foreach ($result->request_skills as $skill) {
            $skill = $skill->skill;
        }

        $result = $this->formatRequestData($result);
        $response = [
            "data" => $result
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Creates a new Mentorship request and saves in the request table
     * Also saves the request skills in the request skills table
     *
     * @param Request $request - request object
     * @param Slack $slack - slack class
     * @return object Response object of created request
     */
    public function add(Request $request, Slack $slack)
    {
        $mentorship_request = self::MODEL;

        $this->validate($request, MentorshipRequest::$rules);
        $user = $request->user();

        // update the user table with the mentee details
        $this->updateUserTable($request, $user->uid);
        $user_array = ["mentee_id" => $user->uid, "status_id" => Status::OPEN];
        $new_record = $this->filterRequest($request->all());
        $new_record = array_merge($new_record, $user_array);
        $created_request = $mentorship_request::create($new_record);

        $primary = $request->all()["primary"];
        $secondary = $request->all()["secondary"];
        $this->mapRequestToSkill($created_request->id, $primary, $secondary);

        // find all mentors matching request we need to send them emails */
        $all_skills = array_merge($primary, $secondary);
        $user_info = UserSkill::wherein('skill_id', $all_skills)->select('user_id')->get()->toArray();
        $mentor_ids = [];

        if ($user_info) {
            $mentor_ids = array_map(function ($user) {
                return $user["user_id"];
            }, $user_info);
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

        $slack_channels_to_notify
            = Config::get("slack.{$app_environment}.new_request_channels");

        $slack->sendMessageToMultipleChannels($slack_channels_to_notify, $slack_message);

        try {
            // get email address of all the people to send the email to
            foreach ($mentor_ids as $mentor_id) {
                $mentor_details = $this->getUserDetails($request, $mentor_id);
                array_push($bulk_email_addresses, $mentor_details["email"]);
            }

            $email_content = [
                "content" => "You might be interested in this mentorship request.
                You can view the details of the request here {$request_url}",
                "title" => "Matching mentorship request"
            ];

            // send the email to the first person and bcc everyone else
            if (sizeof($bulk_email_addresses) > 0) {
                $this->sendEmail($email_content, $bulk_email_addresses[0],
                    array_slice($bulk_email_addresses, 1));
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
     * @param  integer $id Unique ID of the mentorship request
     * @return \Illuminate\Http\JsonResponse
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
         }  catch (Exception $exception) {
             return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
         }

        $new_record = $this->filterRequest($request->all());
        $mentorship_request->fill($new_record)->save();

        if ($request->primary || $request->secondary) {
            $this->mapRequestToSkill($id,
                $request->primary,
                $request->secondary
            );
        }

        return $this->respond(Response::HTTP_CREATED, $mentorship_request);
    }

    /**
     * Edit a mentorship request interested field
     *
     * @param  integer $id Unique ID of the mentorship request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInterested(Request $request, Slack $slack_provider, $id)
    {
        $this->validate($request, MentorshipRequest::$mentee_rules);

        try {
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            $current_user = $request->user();

            if (!$current_user) {
                throw new AccessDeniedException("You don't have permission to edit the mentorship request", 1);
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
            $mentee_details = $this->getUserDetails($request, $mentee_id);
            $mentee_name = $mentee_details["name"];
            $to_address = $mentee_details["email"];
            $email_content = [
                "content" => "{$mentor_name} has indicated interest in mentoring you.
                You can view the details of the request here {$request_url}",
                "title" => "Hello {$mentee_name}"
            ];
            $this->sendEmail($email_content, $to_address);

            // Send a slack notification to a mentee when a mentor shows interest in their request
            $user = User::select('slack_id')
                        ->where('user_id', $mentee_id)
                        ->first();
            $message = "*{$mentor_name}* has indicated interest in mentoring you.
                You can view the details of the request <{$request_url}|here>";
            $slack_provider->sendMessage($user->slack_id, $message);

            return $this->respond(Response::HTTP_CREATED, $mentorship_request);

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
     * @param Slack $slack_provider
     * @param integer $id Unique ID of the mentorship request
     * @return \Illuminate\Http\JsonResponse
     * @throws AccessDeniedException
     */
    public function updateMentor(Request $request,  Slack $slack_provider, $id)
    {
        $this->validate($request, MentorshipRequest::$mentor_update_rules);

        $request->match_date = Date('Y-m-d H:i:s', $request->match_date);

        try {
            $mentorship_request = MentorshipRequest::with('requestSkills')->findOrFail(intval($id));
            $current_user = $request->user();
            $requestSkills = $mentorship_request->requestSkills()->get();
            foreach($requestSkills as $skill) {
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
            $body = $this->getUserDetails($request, $request->mentor_id);
            $to_address = $body["email"];
            $this->sendEmail($content, $to_address);

            // Send the mentor a slack message when notified
            $slack_handle = User::select('slack_id')
                ->where('user_id', $request->input('mentor_id'))
                ->first();
            $message = "{$mentee_name} selected you as a mentor
            You can view the details of the request here {$request_url}";
            $slack_provider->sendMessage($slack_handle->slack_id, $message);

            return $this->respond(Response::HTTP_OK, $mentorship_request);

        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }
    }

    /**
     * Set a request status to cancelled
     *
     * @param Request $request
     * @param integer $id Unique ID used to identify the request
     * @return \Illuminate\Http\JsonResponse
     * @throws AccessDeniedException
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
                RequestSkill::create([
                    "request_id" => $request_id,
                    "skill_id" => $skill,
                    "type" => "primary"
                ]);
            }
        }

        if ($secondary) {
            foreach ($secondary as $skill) {
                RequestSkill::create([
                    "request_id" => $request_id,
                    "skill_id" => $skill,
                    "type" => "secondary"
                ]);
            }
        }
    }

    /**
     * Filter incoming request body to remove object property
     * containing primary and secondary skills
     *
     * @param object $request
     * @return object
     */
    private function filterRequest($request)
    {
        return array_filter($request, function ($value, $key) {
            return $key !== 'primary' && $key !== 'secondary';
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Format request data
     * extracts returned request queries to match data on client side
     *
     * @param object $result
     * @return object
     */
    private function formatRequestData($result)
    {
        $formatted_result = (object) [
            "id" => $result->id,
            "mentee_id" => $result->mentee_id,
            "mentee_email" => $result->user->email ?? '',
            "mentor_id" => $result->mentor_id,
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
     * @param array $request_skills
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
     * format time
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
     * Returns the requests for a particular mentee
     *
     * @param string $user_id
     * @param Request $request request payload
     * @return Response Object
     */
    private function getMenteeRequests($user_id, $request)
    {
        return MentorshipRequest::buildWhereClause($request)
            ->where('mentee_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Method that transforms the mentorship requests into a response object
     *
     * @param array $mentorship_requests
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
     * get user details
     *
     * @param Request $request the request facade
     * @param string $id the user id
     * @return array of the json encoded response
     */
    private function getUserDetails(Request $request, $id)
    {
        $client = new Client();
        $auth_header = $request->header("Authorization");
        $api_url = getenv('AIS_API_URL');
        $response = $client->request('GET', "{$api_url}/users/{$id}", [
            "headers" => ["Authorization" => $auth_header],
            "verify" => false
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Gets all the requests that match a mentor's skill
     *
     * @param string $user_id
     * @return array $mentorship_requests
     */
    private function getAllRequestByMentorSkills($user_id)
    {
        // retrieve mentorship requests that match current user's skills
        $user_skills = UserSkill::with('matchingRequests')
            ->where('user_id', $user_id)
            ->get();

        // pluck out the actual mentorship request from query result
        $mentorship_requests = [];
        foreach ($user_skills as $user_skill) {
            foreach($user_skill->matchingRequests as $user_request) {
                array_push($mentorship_requests, $user_request->request);
            }
        }

        return $mentorship_requests;
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
            Mail::send(['html' => $blade_template], $email_content, function ($msg) use ($to_address, $bcc)
            {
                $msg->subject('Lenken Notification');
                $msg->to([$to_address]);
                $msg->from(['lenken-tech@andela.com']);

                if ($bcc) {
                    $msg->bcc($bcc);
                }

            });
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
     * @param Request $request
     */
    public function updateUserTable(Request $request, $user_id)
    {
        // fetch the user's details from AIS
        $user_info = $this->getUserDetails($request, $user_id);

        // if the user's user_id is not in the table, create a new user with id and email
        User::firstOrCreate(["user_id" => $user_info["id"]], ["email" => $user_info["email"]]);
    }

}
