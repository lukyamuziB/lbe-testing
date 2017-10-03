<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model {
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

    // Relationships
    public function session()
    {
        return $this->belongsTo("App\Models\Session", "session_id", "id");
    }

    public function user()
    {
        return $this->belongsTo("App\Models\User", "user_id", "user_id");
    }
    

    public static function getAverageRatings($userId)
    {
        $ratingValues = [];
        $averageRating = 0;
        $ratingName = '';
        
        $ratings = Rating::select("values")
                    ->where("user_id", $userId)
                    ->get();

        foreach ($ratings as $rating) {
            $userRatings = json_decode($rating->values);
            foreach (get_object_vars($userRatings) as $ratingName => $ratingValue) {
                array_push($ratingValues, $ratingValue);
                $averageRating = array_sum($ratingValues) / count($ratingValues);
            }
        }
        return $averageRating;
    }
}
