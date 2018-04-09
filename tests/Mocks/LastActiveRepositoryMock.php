<?php

namespace Test\Mocks;

use App\Repositories\LastActiveRepository;
use Test\Mocks\RedisMock;

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
