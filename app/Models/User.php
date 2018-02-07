<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $primaryKey = 'user_id'; // or null
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "user_id",
        "slack_id",
        "email"
    ];

    public static $rules = [
        "user_id" => "required|string",
        "slack_id" => "string",
        "email" => "required|string"
    ];

    /**
     * Returns the user's fullname based on the email address
     *
     * @return string $fullname - user's fullname
     */
    public function getFullnameAttribute()
    {
        $emailUsername = explode("@", $this->attributes["email"])[0];

        $names = explode(".", $emailUsername);

        $lastName = isset($names[1]) ? " " . ucfirst($names[1]) : "";
        $fullname = ucfirst($names[0]) . $lastName;

        return $fullname;
    }

    /**
     * Gets the skills of the current user object based on the UserSkills model
     *
     * @return array $skills - the skills belonging to the user object
     */
    public function getSkills()
    {
        $userSkills = UserSkill::with("skill")->where("user_id", $this->user_id)->get();

        $skills = [];
        foreach ($userSkills as $skill) {
            $skills[] = (object)[
                "id" => $skill->skill_id,
                "name" => $skill->skill->name
            ];
        }

        return $skills;
    }

    /**
     * Defines Foreign Key Relationship to the SessionComment model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany("App\Models\SessionComment");
    }
}
