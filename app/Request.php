<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Request extends Model {

    protected $table = 'requests';

    protected $fillable = [
        "mentee_id",
        "mentor_id",
        "title",
        "description",
        "status_id",
        "match_date",
        "pairing",
        "duration"
    ];

    protected $dates = [];

    protected $casts = [
        "interested" => "array",
        "pairing" => 'array',
    ];

    public static $rules = [
        "mentee_id" => "numeric",
        "mentor_id" => "numeric",
        "title" => "required",
        "description" => "required",
        "status_id" => "numeric",
        "match_date" => "date",
        "duration" => "numeric|required",
        "pairing.start_time" => "required|date_format:H:i",
        "pairing.end_time" => "required|date_format:H:i",
        "pairing.days" => "required|array|",
        "pairing.days.*" => "in:monday,tuesday,wednesday,thursday,friday,saturday,sunday",
        "pairing.timezone" => "required|timezone",
        "primary" => "required|array",
        "secondary" => "required|array",
        "primary.*" => "numeric|min:1",
        "secondary.*" => "numeric|min:1"
    ];

    public static $mentee_rules = [
        "interested" => "required|array",
        "interested.*" => "string|regex:/\w+/",
    ];

    public function users()
    {
        return $this->belongsTo("App\User");
    }

    public function requestSkills()
    {
        return $this->hasMany("App\RequestSkill");
    }

    public function status()
    {
        return $this->belongsTo("App\Status");
    }
}
