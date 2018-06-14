<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    
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

    const REQUESTS_MATCHING_USER_SKILLS = "REQUESTS_MATCHING_USER_SKILLS";
    const REQUEST_ACCEPTED_OR_REJECTED = "REQUEST_ACCEPTED_OR_REJECTED";
    const SESSION_NOTIFICATIONS = "SESSION_NOTIFICATIONS";
    const FILE_NOTIFICATIONS = "FILE_NOTIFICATIONS";
    const INDICATES_INTEREST = "INDICATES_INTEREST";
    const WITHDRAWN_INTEREST = "WITHDRAWN_INTEREST";
    const MATCHING_OPEN_REQUEST_SKILLS = "MATCHING_OPEN_REQUEST_SKILLS";
}
