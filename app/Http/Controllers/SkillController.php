<?php

namespace App\Http\Controllers;

use App\Skill;
use App\UserSkill;
use App\Exceptions\Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
                return $this->respond(Response::HTTP_BAD_REQUEST,
                    ["message" => "only alphanumeric characters and hyphens allowed"]);
            }

            $search_query = $input['q'];
            $matched_skill = Skill::where('name', 'iLIKE', '%'.$search_query.'%')->get();

            return $this->respond(Response::HTTP_OK, ["data" => $matched_skill]);
        } else {
            return $this->respond(Response::HTTP_OK, ["data" => $m::withCount('requestSkills', 'userSkills')->orderBy('name')->get()]);
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
        $user_skills = UserSkill::where('user_id', $user_id)
                                ->with('skill')
                                ->get()
                                ->toArray();

        // pluck just skills out
        $skills = array_map(function ($user_skill) {
          return $user_skill["skill"];
        }, $user_skills);

        $response = [
            "data" => $skills
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Creates a new skill and saves in the skills table
     *
     * @param object $request Request
     * @return object Response object of created skill
     */
    public function add(Request $request)
    {
        $this->validate($request, Skill::$rules);

        if (Skill::where('name', 'ilike', $request->name)->exists()) {
            return $this->respond(
                Response::HTTP_CONFLICT, ["message" => "Skill already exists"]
            );
        }

        $skill = Skill::create([
            "name" => $request->name
        ]);

        $response = [
            "data" => ["skill" => $skill, "message" => "Skill was successfully created"]
        ];

        return $this->respond(Response::HTTP_CREATED, $response);
    }

    /**
     * Edit a Skills name field
     *
     * @param integer $id Unique ID of a particular skill
     * @return object response of modified skill and success message
     */
    public function put(Request $request, $id)
    {
        $this->validate($request, Skill::$rules);
        $skill = Skill::find($id);
        if (is_null($skill)) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => "The specified skill request was not found"]);
        }

        if (Skill::where('name', 'ilike', $request->name)->exists()) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => "Skill already exists"]);
        }

        $skill->update($request->all());
        $response = [
            "data" => ["skill" => $skill, "message" => "Skill was successfully modified"]
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Deletes a skill
     *
     * @param object  $request the request object
     * @param integer $id skill id
     *
     * @return object Response object
     */
    public function remove(Request $request, $id)
    {
        try {
            if (UserSkill::where('skill_id', $id)->exists()
                || \App\RequestSkill::where('skill_id', $id)->exists()
            ){
                return $this->respond(Response::HTTP_FORBIDDEN, ["data" => "Skill is currently in use"]);
            }
            
            $skill = Skill::findOrFail($id);
            $skill->delete();

            return $this->respond(Response::HTTP_OK, ["data" => "Skill deleted"]);
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("Skill does not exist!");
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }
    }
}
