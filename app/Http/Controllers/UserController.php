<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Request as MentorshipRequest;
use App\Clients\AISClient;
use App\Models\UserSkill;
use App\Models\Skill;
use App\Models\Session;

class UserController extends Controller
{ 
    use RESTActions;

    /**
     * Gets a user's infomation based on their user id.
     *
     * @return Response object
     */
    public function get(Request $request, AISClient $ais_client, $id)
    {
        $user_details = $ais_client->getUserById($id);

        $user_skill_objects = UserSkill::with('skill')->where('user_id', $id)->get();

        $user_skills = [];

        foreach ($user_skill_objects as $skill) {
            $extracted_skills = (object) [
                "id" => $skill->skill_id,
                "name"=> $skill->skill->name
            ];
            array_push($user_skills, $extracted_skills);
        }

        $request_count = $this->getMenteeRequests($id);
        
        $total_logged_hours = Session::getTotalLoggedHours($id);

        $response = (object) [
            "id" => $user_details["id"],
            "picture" => $user_details["picture"],
            "first_name" => $user_details["first_name"],
            "name" => $user_details["name"],
            "location" => $user_details['location']['name'],
            "cohort" => $user_details["cohort"],
            "roles" => $user_details["roles"],
            "placement" => $user_details["placement"],
            "email" => $user_details["email"],
            "level" => $user_details["level"],
            "skills" => $user_skills,
            "request_count" => $request_count,
            "logged_hours" => $total_logged_hours
        ];
        
        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Returns the requests for a particular mentee
     *
     * @param string $user_id
     * @return integer total number of mentorship request made
     */
    private function getMenteeRequests($user_id)
    {
        return MentorshipRequest::where('mentee_id', $user_id)
            ->count();
    }
}
