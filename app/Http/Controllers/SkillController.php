<?php

namespace App\Http\Controllers;

use App\Skill;
use App\UserSkill;
use App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class SkillController extends Controller
{
    const MODEL = 'App\Skill';

    use RESTActions;

    /**
     * GET all skills or return a specific skill
     *
     * @return json JSON object containing skill(s)
     */
    public function all(Request $request)
    {
        $m = self::MODEL;
        $this->validate($request, array());

        $input = $request->input();

        if (array_keys($input) === ['id']) {
            return $this->getUserSkills($input['id']);
        } else if (array_keys($input) === ['q']) {
            if (!preg_match("/^[a-zA-Z0-9-]+$/", $input['q'])) {
                return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => "only alphanumeric characters and hyphens allowed"]);
            }

            $search_query = $input['q'];
            $matched_skill = Skill::where('name', 'iLIKE', '%'.$search_query.'%')->get();

            return $this->respond(Response::HTTP_OK, ["data" => $matched_skill]);
        } else {
            return $this->respond(Response::HTTP_OK, ["data" => $m::orderBy('name')->get()]);
        }
    }

    /**
     * GET all skills that belongs to a user
     * 
     * @param string $user_id
     *
     * @return json JSON object containing skill(s)
     */
    private function getUserSkills($user_id)
    {
        $user_skills = UserSkill::where('user_id', $user_id)->with('skill')->get();

        $response = [
            'data' => $user_skills
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }
}
