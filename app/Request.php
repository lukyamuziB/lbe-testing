<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Request extends Model
{

    protected $table = 'requests';

    protected $fillable = [
        "mentee_id",
        "mentor_id",
        "title",
        "description",
        "status_id",
        "match_date",
        "pairing",
        "duration",
        "location"
    ];

    protected $dates = [];

    protected $casts = [
        "interested" => "array",
        "pairing" => 'array',
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
        "primary" => "required|array",
        "secondary" => "array",
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

    public function user()
    {
        return $this->belongsTo('App\User', 'mentee_id');
    }

    public function requestSkills()
    {
        return $this->hasMany("App\RequestSkill");
    }

    public function status()
    {
        return $this->belongsTo("App\Status");
    }

    /**
     * Gets the timestamp of how many weeks ago request was made
     *
     * @param string $location
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
     * @param string $location
     * @return mixed string|null
     */
    public static function getLocation($location)
    {
        return ($location === 'ALL') ? null : $location;
    }

    /**
    * Returns an array of the location from which request was made
    * and period when requests were made
    *
    * @return array of location and period of requests
    */
    public static function buildWhereClause($request)
    {
        $selected_date = Request::getTimeStamp($request->input('period'));
        $selected_location = Request::getLocation($request->input('location'));

        return Request::when($selected_date, function($query) use ($selected_date) {
            if ($selected_date) {
                return $query->where('created_at', '>=', $selected_date);
            }
        })
        ->when($selected_location, function($query) use ($selected_location) {
            if($selected_location) {
                return $query->where('location', $selected_location);
            }
        });
    }
}
