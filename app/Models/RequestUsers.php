<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestUsers extends Model
{
    protected $table = "request_users";

    public $timestamps = false;

    protected $fillable = [
        "user_id",
        "role_id",
        "request_id"
    ];

    public static $rules = [
        "user_id" => "required|string",
        "role_id" => "required|numeric",
        "request_id" => "required|numeric"
    ];
    
    /**
     * Defines Foreign Key Relationship to the user model
     *
     * @return Object
     */
    public function user()
    {
        return $this->belongsTo("App\Models\User", "user_id", "id");
    }
}
