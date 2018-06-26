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

    protected $primaryKey = 'id'; // or null
    public $incrementing = false;
    public $timestamps = false;
    protected $appends = ["fullname"];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
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
     * Defines Foreign Key Relationship to the SessionComment model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany("App\Models\SessionComment");
    }

    /**
     * Returns the requests for a particular mentee
     *
     * @param string $userId - user id
     *
     * @return integer total number of mentorship request made
     */
    private function getMenteeRequestCount($userId)
    {
        return Request::where("created_by", $userId)
        ->count();
    }

    /**
     * Gets the skills of a user based on the UserSkills model.
     *
     * @return array $skills User skills
     */
    public function getSkills()
    {
        $userSkills = UserSkill::with("skill")->where("user_id", $this->id)->get();

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
     * Gets statistics of a user.
     *
     * @return array $statistics User statistics
     */
    public function getStatistics()
    {
        $requestCount = $this->getMenteeRequestCount($this->id);

        $sessionDetails = Session::getSessionDetails($this->id);

        $statistics = [];
        $statistics[] = (object)[
            "request_count" => $requestCount,
            "logged_hours" => $sessionDetails["totalHours"],
            "total_sessions" => $sessionDetails["totalSessions"],
        ];

        return (object) $statistics;
    }


    /**
     * Get comments of a user.
     *
     * @return array $userComments User comments
     */
    public function getComments()
    {
        $userId = $this->id;
        $sessionComments = SessionComment::with("user", "session.request")
        ->where(
            function ($query) use ($userId) {
                $query->whereIn(
                    "session_id",
                    Session::where(
                        function ($query) use ($userId) {
                            $query->whereIn(
                                "request_id",
                                RequestUsers::whereUserId($userId)->pluck("request_id")
                            );
                        }
                    )->pluck("id")
                );
            }
        )
        ->get();

        $userComments = [];
        foreach ($sessionComments as $sessionComment) {
            $userComments[] = (object)[
            "date" => $sessionComment->created_at,
            "comment" => $sessionComment->comment,
            "commentor" => $sessionComment->user->fullname,
            "request_title" => $sessionComment->session->request->title
            ];
        }

        return $userComments;
    }

    /**
     * Gets user details for the different profile categories
     *
     * @param $categories User profile categories
     *
     * @return array $userDetails User details containing category details
     */
    public function appendProfileCategoryDetails($categories)
    {
        $userDetails = [];
        if (in_array("skills", $categories)) {
            $userDetails["skills"] = $this->getSkills();
        }

        if (in_array("statistics", $categories)) {
            $userDetails["statistics"] = $this->getStatistics();
        }

        if (in_array("comments", $categories)) {
            $userDetails["comments"] = $this->getComments();
        }

        return $userDetails;
    }
}
