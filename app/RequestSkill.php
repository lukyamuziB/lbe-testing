<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class RequestSkill extends Model {

    protected $fillable = ["request_id", "skill_id", "type"];

    protected $dates = [];

    public static $rules = [
        "request_id" => "required|numeric",
        "skill_id" => "required|numeric",
        "type" => "required|string"
    ];

    public function request()
    {
        return $this->belongsTo("App\Request");
    }
}
