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
     * Gets unapproved sessions that were logged by one of the
     * participants before the specified time
     *
     * @param integer $duration hours since last unapproved session
     *
     * @return array $unapproved_sessions
     */
    public static function getUnapprovedSessionsByTime($duration)
    {
        $threshold_time = Carbon::now()->subHours($duration);

        $unapproved_sessions = Session::with('request.user', 'request.mentor')
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
}
