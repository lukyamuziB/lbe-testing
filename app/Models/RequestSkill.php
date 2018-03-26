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
        return $this->belongsTo("App\Models\Skill");
    }

    public function userSkills()
    {
        return $this->belongsTo("App\Models\UserSkill");
    }
}
