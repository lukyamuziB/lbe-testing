<?php

namespace Test\Mocks;

use App\Repositories\UsersAverageRatingRepository;

class UsersAverageRatingMock extends UsersAverageRatingRepository
{
     /**
     * Populate $redisClient with a mock of what the redis cache will normally return
     */
    public function make()
    {
        $this->redisClient = (object)[
            "average_rating" => "2.7",
            "average_mentor_rating" => "1.7",
            "average_mentee_rating" => "2.0",
            "session_count" => 3,
            "user_id" => "-K_nkl19N6-EGNa0W8LF",
            "email" => "inumidun.amao@andela.com"
        ];
    }

    public function getById($id)
    {
        if ($this->redisClient->user_id === "-K_nkl19N6-EGNa0W8LF") {
            return $this->redisClient;
        }
    }

    public function query($userIds)
    {
        foreach ($userIds as $userId) {
            if ($this->redisClient->user_id === "-K_nkl19N6-EGNa0W8LF") {
                $averageRatings[] = $this->redisClient;
            }
        }
        return $averageRatings;
    }
}
