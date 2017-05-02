<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Parser;
use App\Exceptions\NotFoundException;
use App\Exceptions\AccessDeniedException;
use App\User;
use App\Request as MentorshipRequest;
use App\RequestSkill;

class RequestController extends Controller {

    const MODEL = "App\Request";
    const MODEL2 = "App\RequestSkill";

    use RESTActions;

    /**
     * Gets all Mentorship Request
     * 
     * @return Response Object
     */
    public function all()
    {
        $mentorship_requests = MentorshipRequest::orderBy('created_at', 'desc')->get();
        $results = array();
        foreach ($mentorship_requests as $mentorship_request) {
            $mentorship_request->request_skills = $mentorship_request->requestSkills;
            $mentorship_request->status = $mentorship_request->status;

            foreach ($mentorship_request->request_skills as $skill) {
                $skill = $skill->skill;
            }

            $result = $this->extract_request_data($mentorship_request);

            array_push($results, $result);
        }

        $response = [
            'data' => $results
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
        
        $result->status = $result->status;

        $result = $this->extract_request_data($result);

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
        if ($interested === NULL) {
            $interested = array();
        }
        $request->interested = array_unique(array_merge($interested, $request->interested));
        $mentorship_request->interested = $request->interested;

        $mentorship_request->save();

        return $this->respond(Response::HTTP_CREATED, $mentorship_request);
    }

    /**
     * Maps the skills in the request body by type and saves them in the request_skills table
     * 
     * @param integer $request_id the id of the request
     * @param string $primary type of skill to map
     * @param string $secondary type of skill to map
     * @return void
     */
    private function map_request_to_skills($request_id, $primary, $secondary) {
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
    private function filter_request($request) {
        return array_filter($request, function($value, $key) {
            return $key !== 'primary' && $key !== 'secondary';
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Extract request data
     * extracts returned request queries to match data on client side
     * 
     * @param object $result
     * @return object
     */
    private function extract_request_data($result)
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
            'request_skills' => $this->filter_request_skills($result->request_skills),
            'status' => $result->status->name,
            'created_at' =>$this->format_time($result->created_at),
            'updated_at' => $this->format_time($result->updated_at)

        );

        return $formatted_result;
    }

    /**
     * Filter Request Skills
     * Filter the result from skills table and add to the skills array
     *
     * @param array $request_skills
     * @return array $skills
     */
    private function filter_request_skills($request_skills)
    {
        $skills = array();

        foreach ($request_skills as $request) {
            $result = (object) array(
                'id' => $request->id,
                'request_id' => $request->request_id,
                'skill_id' => $request->skill_id,
                'type' => $request->type,
                'created_at' =>$this->format_time($request->created_at),
                'updated_at' => $this->format_time($request->updated_at),
                'skill_name' => $request->skill->name
            );
            array_push($skills, $result);
        }

        return $skills;
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
}
