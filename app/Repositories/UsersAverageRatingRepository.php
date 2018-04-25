<?php

namespace App\Repositories;

use App\Interfaces\UsersAverageRatingInterface;
use Illuminate\Support\Facades\Redis;

class UsersAverageRatingRepository implements UsersAverageRatingInterface
{
    protected $redisClient;

    /**
     * UsersAverageRatingMock constructor.
     */
    public function __construct()
    {
        $this->make();
    }

    /**
     * Instantiating Redis
     */
    public function make()
    {
        $this->redisClient = new Redis();
    }

    /**
     * @param string $id - a user's Id
     *
     * @return object - user detail
     */
    public function getById($id)
    {
        $userAverageRating = json_decode(
            $this->redisClient::get("users:$id:averageRating")
        );
        return  $userAverageRating;
    }

    /**
     * Queries the cache with userIds
     *
     * @param array $param parameters
     *
     * @return array
     */
    public function query($userIds)
    {
        $averageRatings = [];
        $usersAverageRatings = [];

        if (count($userIds) > 0) {
            foreach ($userIds as $userId) {
                if ($this->redisClient::exists("users:$userId:averageRating")) {
                    $averageRatings[] = "users:".$userId.":averageRating";
                }
            }
            if (count($averageRatings) > 0) {
                $usersAverageRatings = array_map(
                    function ($averageRatings) {
                        return json_decode($averageRatings);
                    },
                    $this->redisClient::mget($averageRatings)
                );
            }
        } else {
            $usersAverageRatings = [];
        }
        return $usersAverageRatings;
    }
}
