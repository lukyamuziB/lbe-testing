<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserSkill;
use App\Skill;

use GuzzleHttp\Client;

class UserController extends Controller
{ 
    /**
     * Gets a user's infomation based on their user id.
     *
     * @return Response object
     */
    public function get(Request $request, $id)
    {
        $client = new Client();

        $auth_header = $request->header("Authorization");

        $staging_url = getenv('API_STAGING_URL');

        $response = $client->request('GET', '{$staging_url}/users/{$id}', [
            'headers' => ['Authorization' => $auth_header],
            'verify' => false
        ]);

        $body = json_decode($response->getbody(), true);

        $user_skill_objects = UserSkill::with('skill')->where('user_id', $id)->get();

        $user_skills = [];

        foreach ($user_skill_objects as $skill) {
            $extracted_skills = (object) [
                "id" => $skill->skill_id,
                "name"=> $skill->skill->name
            ];
            array_push($user_skills, $extracted_skills);
        }

        $response = [
            "data" => $body,
            "skills" => $user_skills
        ];
        
        return $response;
    }
}
