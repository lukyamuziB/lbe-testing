<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
