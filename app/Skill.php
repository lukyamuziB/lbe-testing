<?php
namespace App;

use Illuminate\Database\Eloquent\Model;


class Skill extends Model
{
    protected $fillable = [
        "name",
        "active"
    ];

    public static $rules = [
       "name" => "required|string",
       "active" => "boolean"
    ];

    public function userSkills()
    {
        return $this->hasMany('App\UserSkill', 'skill_id', 'id');
    }
}
