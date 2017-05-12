<?php
namespace App;

use Illuminate\Database\Eloquent\Model;


class Skill extends Model
{
    protected $fillable = ['name'];

    public static $rules = [
       "name" => "required|string"
    ];

    public function userSkills()
    {
        return $this->hasMany('App\UserSkill', 'skill_id', 'id');
    }
}
