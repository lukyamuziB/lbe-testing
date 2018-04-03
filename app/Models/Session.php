<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Session extends Model
{
    protected $table = 'sessions';
    public $timestamps = false;

    protected $fillable = [
      "request_id",
      "date",
      "start_time",
      "end_time",
      "mentee_approved",
      "mentor_approved",
      "mentee_logged_at",
      "mentor_logged_at"
    ];

    public static $rules = [
      "request_id" => "required|numeric",
      "date" => 'date_format:"Y-m-d"',
      "start_time" => 'required|string',
      "end_time" => 'required|string',
      "mentee_approved" => "boolean",
      "mentor_approved" => "boolean",
      "mentee_logged_at" => 'date_format:"Y-m-d H:i:s"',
      "mentor_logged_at" => 'date_format:"Y-m-d H:i:s"'
    ];

    public function request()
    {
        return $this->belongsTo("App\Models\Request", "request_id", "id");
    }

    public function rating()
    {
        return $this->hasOne("App\Models\Rating", "session_id", "id");
    }

    /**
     * Defines Foreign Key Relationship to the session model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function files()
    {
        return $this->belongsToMany("App\Models\File", "session_file", "session_id", "file_id");
    }

    /**
     * Defines Foreign Key Relationship to the RatingComment model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany("App\Models\SessionComment");
    }

    /**
     * Gets unapproved sessions, whose request status is matched, that were logged
     * by one of the participants before the specified time
     *
     * @param integer $duration hours since last unapproved session
     *
     * @return array $unapproved_sessions
     */
    public static function getUnapprovedSessions($duration)
    {
        $threshold_time = Carbon::now()->subHours($duration);

        $unapproved_sessions = Session::with('request')
            ->whereIn(
                "request_id",
                Request::select("id")
                    ->where("status_id", STATUS::MATCHED)
                    ->get()->toArray()
            )
            ->where(
                function ($query) use ($threshold_time) {
                    $query->where('mentor_approved', null)
                        ->where('mentee_approved', true)
                        ->where('mentee_logged_at', '<=', $threshold_time);
                }
            )->orWhere(
                function ($query) use ($threshold_time) {
                    $query->where('mentee_approved', null)
                        ->where('mentor_approved', true)
                        ->where('mentor_logged_at', '<=', $threshold_time);
                }
            )->get();

        return $unapproved_sessions;
    }

    /**
     * Calculates the total logged mentorship hours and sessions of the user and
     * returns the total hours and sessions count
     *
     * @param string $userId - the id of the user
     *
     * @return object - total number of logged mentorship hours and sessions count
     */
    public static function getSessionDetails($userId)
    {
        $totalHours = 0;
        $sessionDetails = [];

        $sessions = Session::select("start_time", "end_time")
            ->where("mentor_approved", true)
            ->where("mentee_approved", true)
            ->whereIn(
                "request_id",
                Request::select("id")
                    ->where("created_by", $userId)
                    ->get()->toArray()
            )->get();

        $sessionDetails["totalSessions"] = count($sessions);


        foreach ($sessions as $session) {
            $timeDifference = abs(
                    strtotime($session->start_time)
                    - strtotime($session->end_time)
                )
                / 3600;

            $totalHours += $timeDifference;
        }

        $sessionDetails["totalHours"] = $totalHours;

        return $sessionDetails;
    }

    /**
     * Find and get a session from the array of sessions based on the date.
     *
     * @param $sessions - array of all logged sessions.
     * @param $date - session date.
     *
     * @return object - the logged session
     */
    public static function findSessionByDate($sessions, $date)
    {
        $formattedSessionDate = Carbon::parse($date);

        return $sessions->first(function ($value, $key) use ($formattedSessionDate) {
            return Carbon::parse($value->date)->eq($formattedSessionDate);
        });
    }

    /**
     * Get session that has already been logged by a request id and a given date
     *
     * @param Number $requestId - id of the mentorship request
     * @param $date
     * @return Array - containing the session object that was logged at that date
     */
    public static function getSessionByRequestIdAndDate($requestId, $date)
    {
        return Session::where("request_id", $requestId)
            ->whereDate('date', $date)
            ->get();
    }
}
