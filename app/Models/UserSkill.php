<?php

namespace App\Models;

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
        return $this->belongsTo("App\Models\Skill");
    }

    public function skill()
    {
        return $this->hasOne("App\Models\Skill", 'id', 'skill_id');
    }

    public function matchingRequests()
    {
        return $this->hasMany('App\Models\RequestSkill', 'skill_id', 'skill_id')->with('request');
    }
}
