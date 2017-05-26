<?php
namespace App\Http\Controllers;

use App\User;
use App\UserSkill;
use App\Status;
use App\Request as MentorshipRequest;
use App\RequestSkill;

use App\Exceptions\Exception;
use App\Exceptions\NotFoundException;
use App\Exceptions\AccessDeniedException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Lcobucci\JWT\Parser;
use GuzzleHttp\Client;

class RequestController extends Controller
{
    const MODEL = "App\Request";
    const MODEL2 = "App\RequestSkill";

    use RESTActions;

    /**
     * Gets all Mentorship Requests
     *
     * @return Response Object
     */
    public function all(Request $request)
    {
        // generic collection to hold requests to be sent as response
        $mentorship_requests =[];
        if ($request->input('self')) {
            $mentorship_requests = $this->getMenteeRequests($request->user()->uid);
        } elseif ($request->input('mentor')) {
            $mentorship_requests = $this->getAllRequestByMentorSkills($request->user()->uid);
        } elseif ($request->input('period')) {
            $limit = $request->input('limit');
            $mentorship_requests = $this->getRequestsByDate($request->input('period'), $limit);
        } else {
            $mentorship_requests = MentorshipRequest::orderBy('created_at', 'desc')->get();
        }
        // transform the result objects into API ready responses
        $transformed_mentorship_requests = $this->transformRequestData($mentorship_requests);
        $response = [
            'data' => $transformed_mentorship_requests
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

        $result = $this->format_request_data($result);
        $response = [
            'data' => $result
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Creates a new Mentorship request and saves in the request table
     * Also saves the request skills in the request skills table
     *
     * @param object $request Request
     * @return object Response object of created request
     */
    public function add(Request $request)
    {
        $mentorship_request = self::MODEL;
        $request_skill = self::MODEL2;

        $this->validate($request, MentorshipRequest::$rules);

        $user = $request->user();
        $user_array = ['mentee_id' => $user->uid, "status_id" => 1];
        $new_record = $this->filter_request($request->all());
        $new_record = array_merge($new_record, $user_array);
        $created_request = $mentorship_request::create($new_record);
        $primary = $request->all()['primary'];
        $secondary = $request->all()['secondary'];

        $this->map_request_to_skills($created_request->id, $primary, $secondary);

        return $this->respond(Response::HTTP_CREATED, $created_request);
    }
    /**
     * Edit a mentorship request
     *
     * @param  integer $id Unique ID of the mentorship request
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, MentorshipRequest::$rules);

        try {
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            if (is_null($mentorship_request)) {
                throw new NotFoundException("the specified request was not found", 1);
            }
            $current_user = $request->user();
            if ($current_user->uid !== $mentorship_request->mentee_id) {
                throw new AccessDeniedException("you don't have permission to edit the mentorship request", 1);
            }
        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (AccessDeniedException $exception) {
            return $this->respond(Response::HTTP_FORBIDDEN, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        $new_record = $this->filter_request($request->all());

        $mentorship_request->fill($new_record)->save();

        if ($request->primary || $request->secondary) {
            $this->map_request_to_skills($id,
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
     */
    public function updateInterested(Request $request, $id)
    {
        $this->validate($request, MentorshipRequest::$mentee_rules);

        try {
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            if (is_null($mentorship_request)) {
                throw new NotFoundException("the specified request was not found", 1);
            }
            $current_user = $request->user();
            if (!$current_user) {
                throw new AccessDeniedException("you don't have permission to edit the mentorship request", 1);
            }
            if (array_keys($request->all()) !== ["interested"]) {
                throw new AccessDeniedException("this request can only accept an array of interested mentors", 1);
            }
        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (AccessDeniedException $exception) {
            return $this->respond(Response::HTTP_FORBIDDEN, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        $interested = $mentorship_request->interested;

        if ($interested === null) {
            $interested = [];
        }

        $request->interested = array_unique(array_merge($interested, $request->interested));
        $mentorship_request->interested = $request->interested;
        $mentorship_request->save();
        $mentor_name = $current_user->name;
        $mentee_id = $mentorship_request->mentee_id;
        $request_url = getenv('BASE_URL') . "/requests/{$id}";

        try {
            $mentee_details = $this->get_user_details($request, $mentee_id);
            $mentee_name = $mentee_details['name'];
            $this->mentee_email = $mentee_details['email'];
            $this->data = [
                'content' => "{$mentor_name} has indicated interest in mentoring you.
                You can view the details of the request here {$request_url}",
                'title' => "Hello {$mentee_name},",
            ];
            Mail::send(['html' => 'email'], $this->data, function ($msg) {
                $msg->subject('Lenken Notification');
                $msg->to([$this->mentee_email]);
                $msg->from(['lenken-tech@andela.com']);
            });
        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        return $this->respond(Response::HTTP_CREATED, $mentorship_request);
    }

    /**
     * Edit a mentorship request mentor_id field
     *
     * @param  integer $id Unique ID of the mentorship request
     */
    public function updateMentor(Request $request, $id)
    {
        $this->validate($request, MentorshipRequest::$mentor_update_rules);

        $request->match_date = Date('Y-m-d H:i:s', $request->match_date);

        try {
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            if (is_null($mentorship_request)) {
                throw new NotFoundException("The specified request was not found", 1);
            }
            $current_user = $request->user();
            if ($current_user->uid !== $mentorship_request->mentee_id) {
                throw new AccessDeniedException("you don't have permission to edit the mentorship request", 1);
            }
        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        $mentorship_request->mentor_id = $request->mentor_id;
        $mentorship_request->match_date = $request->match_date;
        $mentorship_request->status_id = Status::MATCHED;

        $mentorship_request->save();

        $mentee_name = $request->mentee_name;
        $request_url = getenv('BASE_URL') . "/requests/{$id}/mentor";
        $this->data = [
            'content' => "{$mentee_name} selected you as a mentor
            You can view the details of the request here {$request_url}",
            'title' => 'Mentorship interest accepted',
        ];

        try {
            $body = $this->get_user_details($request, $request->mentor_id);
            $this->recipients_email = $body['email'];
            Mail::send(['html' => 'email'], $this->data, function ($msg) {
                $msg->subject('Lenken Notification');
                $msg->to([$this->recipients_email]);
                $msg->from(['lenken-tech@andela.com']);
            });
        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        return $this->respond(Response::HTTP_OK, $mentorship_request);
    }

    /**
    * Set a request status to cancelled
    *
    * @param integer $id Unique ID used to identify the request
    */
    public function cancelRequest(Request $request, $id)
    {
        try {
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            $mentorship_request->status_id = Status::CANCELLED;
            $mentorship_request->save();
            $this->respond(Response::HTTP_OK, ["message" => "Request Cancelled"]);
        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        }

        return $this->respond(Response::HTTP_OK, $mentorship_request);
    }

    /**
     * Maps the skills in the request body by type and saves them in the request_skills table
     *
     * @param integer $request_id the id of the request
     * @param string $primary type of skill to map
     * @param string $secondary type of skill to map
     * @return void
     */
    private function map_request_to_skills($request_id, $primary, $secondary)
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
     * Filter Request
     * Filter incoming request body to remove object property containing primary and secondary
     *
     * @param object $request
     * @return object
     */
    private function filter_request($request)
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
    private function format_request_data($result)
    {
        $formatted_result = (object) array(
            'id' => $result->id,
            'mentee_id' => $result->mentee_id,
            'mentor_id' => $result->mentor_id,
            'title' => $result->title,
            'description' => $result->description,
            'interested' => $result->interested,
            'status_id' => $result->status_id,
            'match_date' => $result->match_date,
            'duration' => $result->duration,
            'pairing' => $result->pairing,
            'request_skills' => $this->format_request_skills($result->request_skills),
            'status' => $result->status->name,
            'created_at' =>$this->format_time($result->created_at),
            'updated_at' => $this->format_time($result->updated_at)
        );
        return $formatted_result;
    }

    /**
     * Format Request Skills
     * Filter the result from skills table and add to the skills array
     *
     * @param array $request_skills
     * @return array $skills
     */
    private function format_request_skills($request_skills)
    {
        $skills = [];

        foreach ($request_skills as $request) {
            $result = (object) array(
                'id' => $request->skill_id,
                'type' => $request->type,
                'name' => $request->skill->name
            );
            array_push($skills, $result);
        }

        return $skills;
    }

    /**
     * Returns the requests for a particular mentee
     *
     * @param string $user_id
     * @return Array array of object matching the mentee's id
     */
    private function getMenteeRequests($user_id)
    {
        return MentorshipRequest::where('mentee_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * format time
     * checks if the given time is null and returns null else it returns the time in the date format
     *
     * @param string $time
     * @return void
     */
    private function format_time($time)
    {
        if ($time == null) {
            return null;
        }

        return date('Y-m-d H:i:s', $time->getTimestamp());
    }

    /**        
     * Returns a list of requests filtered by date        
     *        
     * @param string $period        
     * @param string $limit        
     * @return Response Object        
     */        
    private function getRequestsByDate($period, $limit)        
    {        
        $date = '';        

        switch ($period) {        
            // one week        
            case '1':        
                $date = date('Y-m-d H:i:s', strtotime('-1 week'));        
                break;        

            // last two weeks        
            case '2':        
                $date = date('Y-m-d H:i:s', strtotime('-2 week'));        
                break;        

            // last four weeks (1 month)        
            case '4':        
                $date = date('Y-m-d H:i:s', strtotime('-4 week'));        
                break;        

            // last eight weeks (2 months)        
            case '8':        
                $date = date('Y-m-d H:i:s', strtotime('-8 week'));        
                break;        
            default:        
                $date = date('Y-m-d H:i:s', strtotime('January 1 1970'));        
        }        

        return MentorshipRequest::whereDate('created_at', '>=', $date)        
                                ->orderBy('created_at', 'desc')        
                                ->limit($limit)        
                                ->get();
    }

    /**
     * Method that transforms the mentorship requests into a response object
     *
     * @param array $mentorship_requests
     * @return Response Object
     */
    private function transformRequestData($mentorship_requests)
    {
        $transformed_mentorship_requests = [];

        foreach ($mentorship_requests as $mentorship_request) {
            $mentorship_request->request_skills = $mentorship_request->requestSkills;
            foreach ($mentorship_request->request_skills as $skill) {
                $skill = $skill->skill;
            }
            $transformed_mentorship_request = $this->format_request_data($mentorship_request);
            array_push($transformed_mentorship_requests, $transformed_mentorship_request);
        }

        return $transformed_mentorship_requests;
    }

    /**
     * get user details
     *
     * @param array $request the request facade
     * @param string $id the user id
     * @return array of the json encoded response
     */
    private function get_user_details($request, $id)
    {
        $client = new Client();
        $auth_header = $request->header("Authorization");
        $staging_url = getenv('API_STAGING_URL');
        $response = $client->request('GET', "{$staging_url}/users/{$id}", [
            'headers' => ['Authorization' => $auth_header],
            'verify' => false
        ]);

        return json_decode($response->getBody(), true);
    }
    
    /**
     * Gets all the requests that match a mentor's skill
     *
     * @param string $user_id
     * @return Object Response Object
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
}
