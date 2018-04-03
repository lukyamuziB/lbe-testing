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

    /**
     * Gets mentors average rating and email
     *
     * @param array $mentors data
     *
     * @return array $mentorsDetails
     */
    public static function getMentorsAverageRatingAndEmail($mentors)
    {
        $mentorsDetails = [];
        foreach($mentors as $mentor) {
            $ratings = [];
            for ($i=0; $i < count($mentor); ++$i) {
                $ratings[] = get_object_vars(json_decode($mentor[$i]["values"]));
                $averageRating = 0;
                foreach($ratings as $rating) {
                    $ratingValues = (
                        $rating["teaching"] + $rating["reliability"]
                        + $rating["availability"] + $rating["usefulness"]
                        + $rating["knowledge"]
                    )/count($rating);
                    $averageRating += $ratingValues;
                }
            }
                $mentorDetails["average_rating"] = number_format($averageRating/count($mentor), 1);
                $mentorDetails["email"] = $mentor[0]["user"]["email"];
                $mentorDetails["session_count"] = count($mentor);
                $mentorsDetails[] = $mentorDetails;
        }
        return $mentorsDetails;
    }
}
