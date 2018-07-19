<?php

namespace Test\Mocks;

use App\Repositories\LastActiveRepository;

class LastActiveRepositoryMock extends LastActiveRepository
{
    /**
     * Instantiates the RedisMock to replace a typical Redis Cache
     */
    public function make()
    {
        $this->redisClient = new RedisMock();
    }
}
