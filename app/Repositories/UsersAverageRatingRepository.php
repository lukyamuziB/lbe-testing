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
     * @param array $userIds - user ids
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

    /**
     * Gets users rating details
     *
     * @return array - users rating details
     */
    public function getAll()
    {
        $userRatingKeys = $this->redisClient::keys("*:averageRating");
        $usersAverageRating = [];

        if (count($userRatingKeys) > 0) {
            $usersAverageRating = array_map(
                function ($averageRatings) {
                    return json_decode($averageRatings);
                },
                $this->redisClient::mget($userRatingKeys)
            );
        }
        return $usersAverageRating;
    }

    /**
     * Gets rating details by range
     *
     * @param array $averageRatings
     * @param array $ratingValues
     *
     * @return array - users rating details
     */
    public function getRatingsByRange($averageRatings, $ratingValues)
    {
        $usersAverageRatings = [];

        foreach ($averageRatings as $averageRating) {
            if (in_array(round($averageRating->average_rating), $ratingValues)) {
                $usersAverageRatings[] = $averageRating;
            }
        }
        return $usersAverageRatings;
    }

    /**
     * Gets user ids from rating details
     *
     * @param array $usersAverageRatings
     *
     * @return array - user ids
     */
    public function getUserIdsFromRatings($usersAverageRatings)
    {
        $userIds = [];

        foreach ($usersAverageRatings as $userAverageRatings) {
            $userIds[] = $userAverageRatings->user_id;
        }
        return $userIds;
    }

    /**
     * Gets user Ids by ratings
     *
     * @param array $params
     *
     * @return array - user ids
     */
    public function getUserIdsByRatings($params)
    {
        $averageRatings = $this->getAll();
        $usersAverageRatings = $this->getRatingsByRange($averageRatings, $params);
        $userIds = $this->getUserIdsFromRatings($usersAverageRatings);

        return $userIds;
    }
}
