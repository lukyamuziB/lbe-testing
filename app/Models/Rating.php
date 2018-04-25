<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = "ratings";
    protected $primaryKey = ['session_id', 'user_id'];
    public $incrementing = false;

    protected $fillable = [
        "user_id",
        "session_id",
        "values",
        "scale",
    ];

    public static $rules = [
        // Validation rules
        "user_id" => "string",
        "session_id"   => "required|numeric",
        "values" => "required|array",
        "scale" => "required|integer",
    ];

    /**
     * Defines Foreign Key Relationship to the session model
     *
     * @return Object
     */
    public function session()
    {
        return $this->belongsTo("App\Models\Session", "session_id", "id");
    }

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
