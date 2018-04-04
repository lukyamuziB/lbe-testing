<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Request extends Model
{

    protected $table = "requests";

    protected $fillable = [
        "title",
        "description",
        "duration",
        "interested",
        "status_id",
        "match_date",
        "pairing",
        "location",
        "created_at",
        "created_by",
        "request_type_id",
    ];

    protected $dates = [];

    protected $casts = [
        "interested" => "array",
        "pairing" => "array",
    ];

    public static $rules = [
        "title" => "required",
        "description" => "required",
        "status_id" => "numeric",
        "match_date" => "date_format: Y-m-d H:i:s",
        "duration" => "numeric|required",
        "pairing.start_time" => "required|date_format:H:i",
        "pairing.end_time" => "required|date_format:H:i",
        "pairing.days" => "required|array|",
        "pairing.days.*"
        => "in:monday,tuesday,wednesday,thursday,friday,saturday,sunday",
        "pairing.timezone" => "required|timezone",
        "primary" => "required|array|max:3",
        "secondary" => "array|max:3",
        "preRequisite" => "array|max:3",
        "primary.*" => "numeric|min:1",
        "secondary.*" => "numeric|min:1",
        "preRequisite.*" => "numeric|min:1",
        "location" => "string",
        "created_by" => "string",
        "request_type_id" => "numeric",
    ];

    public static $mentee_rules = [
        "interested" => "required|array",
        "interested.*" => "string|regex:/\w+/"
    ];

    public static $mentor_update_rules = [
        "mentor_id" => "required|string",
        "mentee_name" => "required|string",
        "match_date" => "numeric|required"
    ];

    public static $acceptOrRejectUserRules = [
        "interestedUserId" => "required|string",
        "interestedUserName" => "required|string",
    ];

    public function session()
    {
        return $this->hasMany("App\Models\Session", "request_id");
    }

    /**
     * Defines Foreign Key Relationship to the RatingComment model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(SessionComment::class);
    }

    /**
     * Returns an object containing the mentor's details
     *
     * @return Object
     */
    public function getMentorAttribute()
    {
        return RequestUsers::with("user")
                            ->where("request_id", $this->attributes["id"])
                            ->where("role_id", Role::MENTOR)
                            ->first()["user"];
    }

    /**
     * Returns an object containing the mentee's details
     *
     * @return Object
     */
    public function getMenteeAttribute()
    {
        return RequestUsers::with("user")
                            ->where("request_id", $this->attributes["id"])
                            ->where("role_id", Role::MENTEE)
                            ->first()["user"];
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
            isset($params["startDate"]) && isset($params["endDate"]),
            function ($query) use ($params) {
                return $query
                    ->where([
                        ["created_at", ">=", $params["startDate"]],
                        ["created_at", "<", $params["endDate"]]
                    ]);
                return $query;
            }
        )
        ->when(
            isset($params["mentee"]),
            function ($query) use ($params) {
                $requestsWhereMentee = RequestUsers::where("user_id", $params["mentee"])
                    ->where("role_id", Role::MENTEE)
                    ->pluck("request_id");

                return $query->whereIn("id", $requestsWhereMentee);
            }
        )
        ->when(
            isset($params["mentor"]),
            function ($query) use ($params) {
                $mentorId = $params["mentor"];
                return $query->with('requestSkills')
                    ->whereRaw("interested::jsonb @> to_jsonb('$mentorId'::TEXT)")
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
     * Returns the mentorship request based on the params
     *
     * @param string $params - request filters
     *
     * @return Array - Filtered request
     */
    public static function buildPoolFilterQuery($params)
    {
        $mentorshipRequests = Request::when(
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
            isset($params["category"]) && $params["category"] == "recommended",
            function ($query) use ($params) {
                $userId =$params["user"];
                return $query->whereHas("requestSkills", function ($query) use ($userId) {
                    $query->whereHas("skill", function ($query) use ($userId) {
                        $query->whereIn("skill_id", UserSkill::whereUserId($userId)->pluck("skill_id"));
                    });
                });
            }
        )
        ->when(
            isset($params["lengths"]),
            function ($query) use ($params) {
                return $query->whereIn("duration", $params["lengths"]);
            }
        )
        ->when(
            isset($params["locations"]),
            function ($query) use ($params) {
                return $query->whereIn("location", $params["locations"]);
            }
        )
        ->when(
            isset($params["status"]),
            function ($query) use ($params) {
                return $query->whereIn("status_id", $params["status"]);
            }
        )
        ->when(
            isset($params["startDate"]) && isset($params["endDate"]),
            function ($query) use ($params) {
                return $query->whereBetween("created_at", [$params["startDate"], $params["endDate"]]);
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
            ->with("requestSkills.skill")
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

    /**
     * Get logged sessions dates given a request id.
     *
     * @return array - session dates.
     */
    public function getLoggedSessionDates()
    {
        $loggedSessionsDates = Session::where("request_id", $this->id)
            ->where(["mentee_approved" => true, "mentor_approved" => true])
            ->pluck("date")
            ->all();
        return $this->formatLoggedSessionDates($loggedSessionsDates);
    }

    /**
     * Format logged sessions dates.
     *
     * @return array - formatted logged session dates.
     */
    public function formatLoggedSessionDates($loggedSessionDates)
    {
        $formattedLoggedSessionDates = array_map(function ($loggedSessionDate) {
            return Carbon::parse($loggedSessionDate)->toDateString();
        }, $loggedSessionDates);

        return $formattedLoggedSessionDates;
    }

    /**
     * Get all logged sessions.
     *
     * @return array - logged sessions.
     */
    public function getLoggedSessions()
    {
        $sessions = Session::with("files")->where("request_id", $this->id)
            ->get();

        return $sessions;
    }

    /**
     * Defines Foreign Key Relationship to the RequestType model
     *
     * @return Object
     */
    public function requestType()
    {
        return $this->hasOne("App\Models\RequestType", "request_type_id", "id");
    }
}
