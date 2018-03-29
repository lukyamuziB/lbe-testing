<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

class LastActiveRepository
{

    /**
     * @param string $id - a user's Id
     * @param string $time - time to be cached on redis
     */
    public function set($id, $time)
    {
        Redis::set("users:$id:lastActive", $time);
    }

    /**
     * @param string $id - a user's Id
     *
     * @return string - last active of a user
     */
    public function get($id)
    {
        return Redis::get("users:$id:lastActive");
    }
}
