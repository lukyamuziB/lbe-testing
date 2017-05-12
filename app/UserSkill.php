<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class UserSkill extends Model
{
    protected $fillable = ["user_id", "skill_id"];

    protected $dates = [];

    public static $rules = [
        "user_id" => "required|string",
        "skill_id" => "required|numeric"
    ];

    public function skills()
    {
        return $this->belongsTo("App\Skill");
    }

    public function skill()
    {
        return $this->hasOne("App\Skill", 'id', 'skill_id');
    }
}
