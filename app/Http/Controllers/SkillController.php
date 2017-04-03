<?php

namespace App\Http\Controllers;

use App\Skill;
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

        if (count($input) > 0) {

            if (!array_key_exists('q', $input)) {
                return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => "invalid search criteria"]);
            } else {
                $criteria = $request->input("q");

                if (!preg_match("/^[a-zA-Z0-9]+$/", $criteria)) {
                    return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => "only alphanumeric characters allowed"]);
                }

                $matched_skill = Skill::findMatching($criteria);

                if ($matched_skill->isEmpty()) {
                    return $this->respond(Response::HTTP_NOT_FOUND, ["message" => "skill not found"]);
                }

                return $this->respond(Response::HTTP_OK, ["data" => $matched_skill->all()]);
            }
        } else {
            return $this->respond(Response::HTTP_OK, ["data" => $m::all()]);
        }
    }
}
