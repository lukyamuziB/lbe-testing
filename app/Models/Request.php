<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Request extends Model
{

    protected $table = "requests";

    protected $fillable = [
        "mentee_id",
        "mentor_id",
        "title",
        "description",
        "status_id",
        "match_date",
        "pairing",
        "duration",
        "location",
        "created_at",
        "interested"
    ];

    protected $dates = [];

    protected $casts = [
        "interested" => "array",
        "pairing" => "array",
    ];

    public static $rules = [
        "mentee_id" => "string",
        "mentor_id" => "string",
        "title" => "required",
        "description" => "required",
        "status_id" => "numeric",
        "match_date" => "date_format: Y-m-d H:i:s",
        "duration" => "numeric|required",
        "pairing.start_time" => "required|date_format:H:i",
        "pairing.end_time" => "required|date_format:H:i",
        "pairing.days" => "required|array|",
        "pairing.days.*" => "in:monday,tuesday,wednesday,thursday,friday,saturday,sunday",
        "pairing.timezone" => "required|timezone",
        "primary" => "required|array|max:3",
        "secondary" => "array|max:3",
        "primary.*" => "numeric|min:1",
        "secondary.*" => "numeric|min:1",
        "location" => "string",
    ];

    public static $mentee_rules = [
        "interested" => "required|array",
        "interested.*" => "string|regex:/\w+/",
    ];

    public static $mentor_update_rules = [
        "mentor_id" => "required|string",
        "mentee_name" => "required|string",
        "match_date" => "numeric|required"
    ];
    
    /**
     * Defines Foreign Key Relationship to the user model
     *
     * @return Object
     */
    public function mentee()
    {
        return $this->belongsTo("App\Models\User", "mentee_id");
    }
    
    /**
     * Defines Foreign Key Relationship to the user model
     *
     * @return Object
     */
    public function mentor()
    {
        return $this->belongsTo("App\Models\User", "mentor_id");
    }
    
    /**
     * Defines Foreign Key Relationship to the skill model
     *
     * @return Object
     */
    public function requestSkills()
    {
        return $this->hasMany("App\Models\RequestSkill");
    }

    public function sessions()
    {
        return $this->hasMany("App\Models\Session");
    }
    
    /**
     * Defines Foreign Key Relationship to the cancelledRequest model
     *
     * @return Object
     */
    public function requestCancellationReason()
    {
        return $this->hasOne("App\Models\RequestCancellationReason");
    }
    /**
     * Defines Foreign Key Relationship to the status model
     *
     * @return Object
     */
    public function status()
    {
        return $this->belongsTo("App\Models\Status");
    }

    /**
     * Gets the timestamp of how many weeks ago request was made
     *
     * @param string $period - the period od days
     *
     * @return mixed string|null
     */
    public static function getTimeStamp($period)
    {
        $date = (int)$period;

        return ($date) ? Carbon::now()->subWeeks($date)->__toString() : null;
    }

    /**
     * Sets the location to be returned in the where clause
     *
     * @param string $location - the request location
     *
     * @return mixed string|null
     */
    public static function getLocation($location)
    {
        return ($location === "ALL") ? null : $location;
    }

    /**
     * Returns the mentorship request based on the param
     *
     * @param string $params - request parameters
     *
     * @return array of request based on the param
     */
    public static function buildQuery($params)
    {
        $mentorshipRequests = Request::when(
            isset($params["search_query"]),
            function ($query) use ($params) {
                $searchQuery = $params["search_query"];
                return $query->whereHas(
                    "mentor",
                    function ($query) use ($searchQuery) {
                        $query-> where("email", "iLIKE", "%".$searchQuery."%");
                    }
                )->orWhereHas(
                    "mentee",
                    function ($query) use ($searchQuery) {
                        $query -> where("email", "iLIKE", "%".$searchQuery."%");
                    }
                );
            }
        )
        ->when(
            isset($params["status"]),
            function ($query) use ($params) {
                return $query->whereIn("status_id", $params["status"]);
            }
        )
        ->when(
            isset($params["skills"]),
            function ($query) use ($params) {
                return $query->whereHas(
                    "requestSkills",
                    function ($query) use ($params) {
                                $query->whereIn("skill_id", $params["skills"]);
                    }
                );
            }
        )
        ->when(
            isset($params["date"]),
            function ($query) use ($params) {
                $date = Request::getTimeStamp($params["date"]);
                if ($params["date"]) {
                    return $query
                            ->where("created_at", ">=", $date);
                }
                return $query;
            }
        )
        ->when(
            isset($params["mentee_id"]),
            function ($query) use ($params) {
                return $query->where("mentee_id", $params["mentee_id"]);
            }
        )
        ->when(
            isset($params["mentor_id"]),
            function ($query) use ($params) {
                $user_id = $params["mentor_id"];
                return $query->with('requestSkills')
                    ->whereRaw("interested::jsonb @> to_jsonb('$user_id'::TEXT)")
                    ->orderBy('created_at', 'desc');
            }
        )
        ->when(
            isset($params["location"]),
            function ($query) use ($params) {
                return $query->where("location", $params["location"]);
            }
        );
        
        return $mentorshipRequests;
    }

    /**
     * Get all unmatched requests that have been made over the given
     * duration. i.e a duration of 24 gets all unmatched requests created
     * that are older than 24hours.
     *
     * @param int $duration - threshold duration for query.
     * @param array $params - parameter key value pairs for additional queries
     * on the request model.
     *
     * @return array
     */
    public static function getUnmatchedRequests($duration = 0, $params = [])
    {
        $thresholdDate = Carbon::now()->subHours(intval($duration));
        $params["status"] = [Status::OPEN];

        $unmatchedRequests = Request::buildQuery($params)
            ->with("requestSkills.skill", "mentee")
            ->whereDate("created_at", "<=", $thresholdDate)
            ->orderBy("created_at", "asc");
    
        return $unmatchedRequests;
    }

    /**
     * Get all unmatched requests that have mentors who indicated interest
     *
     * @return object
     */
    public static function getUnmatchedRequestsWithInterests($duration = 3)
    {
        $thresholdDate =  Carbon::now()->subDays($duration);
        $unmatchedRequests = Request::with("requestSkills.skill", "mentee")
            ->where("status_id", Status::OPEN)
            ->where("interested", '!=', null)
            ->whereDate("created_at", "<", $thresholdDate)
            ->orderBy("created_at", "asc")
            ->get();
        return ($unmatchedRequests);
    }
}
