<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $primaryKey = 'user_id'; // or null
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "user_id",
        "slack_id",
        "email"
    ];

    public static $rules = [
        "user_id" => "required|string",
        "slack_id" => "string",
        "email" => "required|string"
    ];

    public static $slack_update_rules = [
        "slack_handle" => "string|regex:/^@\w+.?\w+$/|required",
    ];

    public static $slack_send_rules = [
        "channel" => "string|required",
        "text" => "string|required",
    ];
}
