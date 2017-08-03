<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Request as MentorshipRequest;
use App\Clients\AISClient;
use App\Models\UserSkill;
use App\Models\Skill;

use GuzzleHttp\Client;

class UserController extends Controller
{ 
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

        $response = [
            "data" => $user_details,
            "skills" => $user_skills,
            "request_count" => $request_count
        ];
        
        return $response;
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
