<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionRating extends Model {
    protected $table = "session_ratings";
    protected $primaryKey = ['session_id', 'user_id'];
    public $incrementing = false;

    protected $fillable = [
        "user_id",
        "session_id",
        "ratings",
        "scale"
    ];

    public static $rules = [
        // Validation rules
        "user_id" => "required|string",
        "session_id"   => "required|numeric",
        "ratings" => "required|array",
        "scale" => "required|integer"
    ];

    // Relationships
    public function session()
    {
        return $this->belongsTo("App\Session", "session_id", "id");
    }

    public function user()
    {
        return $this->belongsTo("App\User", "user_id", "user_id");
    }
    
}
