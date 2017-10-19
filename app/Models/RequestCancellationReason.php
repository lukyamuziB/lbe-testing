<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestCancellationReason extends Model
{
    protected $table = "request_cancellation_reasons";
    protected $fillable = ["request_id", "user_id", "reason"];
    public static $rules = [
        "request_id" => "required|numeric",
        "user_id" => "required|string",
        "reason" => "string"
    ];

    public function request()
    {
        return $this->belongsTo("App\Models\Request");
    }

    public function user()
    {
        return $this->belongsTo("App\Models\User", "user_id");
    }
}
