<?php

namespace tests\App\Http\Lenken\Controller;

use App\Http\Lenken\Controller\Test;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TestSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Test::class);
    }
}
