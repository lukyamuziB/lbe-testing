<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestSkill extends Model
{

    protected $fillable = ["request_id", "skill_id", "type"];

    protected $dates = [];

    public static $rules = [
        "request_id" => "required|numeric",
        "skill_id" => "required|numeric",
        "type" => "required|string"
    ];

    public function request()
    {
        return $this->belongsTo("App\Models\Request");
    }

    public function skill()
    {
        return $this->belongsTo("App\Models\Skill")->withTrashed();
    }

    public function userSkills()
    {
        return $this->belongsTo("App\Models\UserSkill");
    }

    /**
     * Maps the skills in the request body by type and
     * saves them in the request_skills table
     *
     * @param integer $requestId the id of the request
     * @param array   $skills    skill to map
     * @param string  $type      the type of skill to map
     *
     * @return void
     */
    public static function mapRequestToSkill($requestId, $skills, $type)
    {
        if ($skills) {
            foreach ($skills as $skill) {
                RequestSkill::create(
                    [
                        "request_id" => $requestId,
                        "skill_id" => $skill,
                        "type" => $type
                    ]
                );
            }
        }
    }
}
