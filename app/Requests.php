<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Requests extends Model {

    protected $fillable = ["mentee_id", "mentor_id", "title", "description", "status_id"];

    protected $dates = [];

    public static $rules = [
        "mentee_id" => "numeric",
        "mentor_id" => "numeric",
        "title" => "required",
        "description" => "required",
        "status_id" => "numeric",
    ];

    public function users()
    {
        return $this->belongsTo("App\User");
    }


}
