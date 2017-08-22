<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{   
    const INDICATES_INTEREST = "INDICATES_INTEREST";
    const SELECTED_AS_MENTOR = "SELECTED_AS_MENTOR";
    const LOG_SESSIONS_REMINDER = "LOG_SESSIONS_REMINDER";
    const WEEKLY_REQUESTS_REPORTS = "WEEKLY_REQUESTS_REPORTS";
    
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        "id",
        "default",
        "description"
    ];

    public static $rules = [
        "id" => "required|string|regex:/^([A-Z_]*$)/",
        "default" => "required|string",
        "description" => "string"
    ];
    public static $update_rules = [
        "id" => "string|regex:/^([A-Z_]*$)/",
        "default" => "required|string",
        "description" => "string"
    ];
}
