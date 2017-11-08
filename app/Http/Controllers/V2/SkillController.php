<?php

namespace App\Http\Controllers\V2;

use App\Models\Skill;
use Illuminate\Http\Response;

/**
 * Class SkillController
 *
 * @package App\Http\Controllers\V2
 */
class SkillController extends Controller
{
    use RESTActions;
    /**
     * GET all skills that have at least one request
     *
     * @return json - JSON object containing skill(s)
     */
    public function getSkillsWithRequests()
    {
        $skills = Skill::whereHas("requestSkills")->get();

        return $this->respond(Response::HTTP_OK, $skills);
    }
}
