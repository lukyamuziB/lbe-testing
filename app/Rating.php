<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model {
    protected $table = "ratings";
    protected $primaryKey = ['session_id', 'user_id'];
    public $incrementing = false;

    protected $fillable = [
        "user_id",
        "session_id",
        "values",
        "scale"
    ];

    public static $rules = [
        // Validation rules
        "user_id" => "string",
        "session_id"   => "required|numeric",
        "values" => "required|array",
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
