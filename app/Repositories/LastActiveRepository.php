<?php

namespace App\Repositories;

use App\Interfaces\LastActiveRepositoryInterface;
use Illuminate\Support\Facades\Redis;

class LastActiveRepository implements LastActiveRepositoryInterface
{
    protected $redisClient;

    /**
     * LastActiveRepositoryMock constructor.
     */
    public function __construct()
    {
        $this->make();
    }

    /**
     * Populate $model from Redis cache or make it an empty array if cache is empty
     */
    public function make()
    {
        $this->redisClient = new Redis();
    }

    /**
     * @param string $id - a user's Id
     * @param string $time - time to be cached on redis
     */
    public function set($id, $time)
    {
        $this->redisClient::set("users:$id:lastActive", $time);
    }

    /**
     * @param string $id - a user's Id
     *
     * @return string - last active of a user
     */
    public function get($id)
    {
        return $this->redisClient::get("users:$id:lastActive");
    }

    /**
     * Queries the model with specified parameters
     *
     * @param array $userIds - ids of users
     *
     * @return Response object - response object
     */
    public function query($userIds)
    {
        $lastActivesQuery = [];
        if (count($userIds) > 0) {
            foreach ($userIds as $userId) {
                $lastActivesQuery[] = "users:".$userId.":lastActive";
            }
            $usersLastActive = $this->redisClient::mget($lastActivesQuery);
        } else {
            $usersLastActive = [];
        }
        return array_combine($userIds, $usersLastActive);
    }
}
