<?php

namespace App\Http\Controllers\V2;

use App\Models\Skill;
use App\Models\UserSkill;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class UserController
 *
 * @package App\Http\Controllers\V2
 */
class UserController extends Controller
{
    use RESTActions;

    /**
     * Add a user skill
     *
     * @param Request $request - the request object
     * @param integer $userId - user id
     *
     * @throws NotFoundException|ConflictException
     *
     * @return object - Response object
     */
    public function addUserSkill(Request $request, $userId)
    {
        $skillId = $request->input('skill_id');

        if (!Skill::find($skillId)) {
            throw new NotFoundException("Skill does not exist.");
        }

        if (UserSkill::where('skill_id', $skillId)->where('user_id', $userId)
            ->exists()) {
            throw new ConflictException("User skill already exists.");
        }

        UserSkill::create(
            ["skill_id" => $skillId, "user_id" => $userId]
        );

        return $this->respond(Response::HTTP_CREATED, ["message" => "User skill added."]);
    }

    /**
     * Delete a user skill
     *
     * @param integer $userId - user id
     * @param integer $skillId - skill id
     *
     * @throws NotFoundException
     *
     * @return object - Response object
     */
    public function deleteUserSkill($userId, $skillId)
    {
        $skill = UserSkill::where('skill_id', $skillId)->where('user_id', $userId);

        if (!$skill->exists()) {
            throw new NotFoundException("User skill does not exist.");
        }

        $skill->delete();

        return $this->respond(Response::HTTP_OK, ["message" => "User skill deleted."]);
    }
}
