<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RequestType;

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

    /**
     * Gets the ratings of the user and calculate the average and total ratings
     *
     * @param string $userId -The logged in user's id
     *
     * @return object - The average rating of the user to one decimal place
     * and total ratings count
     */
    public static function getRatingDetails($userId)
    {
        $ratingValues = [];
        $averageRating = 0;
        $averageMentorRating = 0;
        $averageMenteeRating = 0;
        $ratingDetails = [];

        $ratings = Rating::with("session.request")->where("user_id", $userId)
                    ->get();

        foreach ($ratings as $rating) {
            $userRatings = json_decode($rating->values);
            foreach (get_object_vars($userRatings) as $ratingValue) {
                $ratingValues[]= $ratingValue;
                $averageRating = number_format((array_sum($ratingValues) / count($ratingValues)), 1);
            }

            if ($rating->session->request->request_type_id === RequestType::MENTEE_REQUEST) {
                $averageMentorRating = $averageRating;
            } else {
                $averageMenteeRating = $averageRating;
            }
        }

        $ratingDetails["total_ratings"] = count($ratings);
        $ratingDetails["average_rating"] = ($averageMentorRating + $averageMenteeRating)/2;
        $ratingDetails["average_mentor_rating"] = $averageMentorRating;
        $ratingDetails["average_mentee_rating"] = $averageMenteeRating;

        return $ratingDetails;
    }
}
