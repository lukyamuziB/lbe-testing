<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\Rating;
use Exception;

 /**
  * Class CacheUsersAverageRatingCommand
  *
  * @package App\Console\Commands
  */
class CacheUsersAverageRatingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "cache:user-average-rating";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Calculates and cache users average ratings";

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        try {
            $cachedRatingsCount = Redis::get("count:allRatings");
            $newRatingsCount = Rating::count();

            if ($cachedRatingsCount < $newRatingsCount) {
                $userIds = Rating::orderBy("created_at", "DESC")
                                    ->take($newRatingsCount - $cachedRatingsCount)
                                    ->pluck("user_id")->unique();

                $usersRatings = Rating::whereIn("user_id", $userIds)
                                        ->get()
                                        ->groupBy("user_id");

                $usersAverageRating = [];
                $keys = [];
                foreach ($usersRatings as $userId => $userRatings) {
                    $usersAverageRating[] = json_encode($this->getRatingDetails($userRatings));
                    $keys[] = "users:$userId:averageRating";
                }

                Redis::mset(array_combine($keys, $usersAverageRating));
                Redis::set("count:allRatings", $newRatingsCount);

                $this->info("Users average rating cached successfully.");
            } else {
                $this->info("No average ratings to be cached.");
            }
        } catch (Exception $e) {
            $this->error("An error occurred, unable to cache users average rating.");
        }
    }

    /**
     * Get users average ratings
     *
     * @param string $userRatings
     *
     * @return object - The user rating details
     */
    public static function getRatingDetails($userRatings)
    {
        $cummulativeRatingValues = [];
        $ratingDetails = [];
        $mentorRatingValues = [];
        $menteeRatingValues = [];

        foreach ($userRatings as $userRating) {
            $ratingValues = json_decode($userRating->values);

            foreach (get_object_vars($ratingValues) as $ratingValue) {
                $cummulativeRatingValues[] = $ratingValue;

                if ($userRating->user_id === $userRating->session->request->mentee->id) {
                    $menteeRatingValues[] = $ratingValue;
                } else {
                    $mentorRatingValues[] = $ratingValue;
                }
            }
        }
        $ratingDetails["user_id"] = $userRating->user_id;

        $ratingDetails["session_count"] = count($userRatings);

        $ratingDetails["average_rating"] = number_format(
            (array_sum($cummulativeRatingValues) / count($cummulativeRatingValues)),
            1
        );
        $ratingDetails["average_mentor_rating"] = sizeof($mentorRatingValues) > 0 ? number_format(
            (array_sum($mentorRatingValues) / count($mentorRatingValues)),
            1
        ) : 0;
        $ratingDetails["average_mentee_rating"] = sizeof($menteeRatingValues) > 0 ? number_format(
            (array_sum($menteeRatingValues) / count($menteeRatingValues)),
            1
        ) : 0;

        return $ratingDetails;
    }
}
