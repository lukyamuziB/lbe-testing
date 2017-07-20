<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Skill extends Model
{
    use SoftDeletes;

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
        return $this->hasMany('App\Models\UserSkill', 'skill_id', 'id');
    }

    public function requestSkills()
    {
        return $this->hasMany('App\Models\RequestSkill', 'skill_id', 'id');
    }
}
