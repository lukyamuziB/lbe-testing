<?php

namespace Tests\App\Models;

use TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    /**
     * Test setup.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        User::create(
            [
                "user_id" => "-K_nkl1kolop909",
                "email" => "timothy.kyadondo@andela.com",
                "slack_id" => "C655PE124",
            ]
        );
    }

    /**
     * Test fullname is returned when accessed.
     *
     * @return void
     */
    public function testGetFullnameAttributeSuccess()
    {
        $user = User::find("-K_nkl1kolop909");
        $this->assertEquals("Timothy Kyadondo", $user->fullname);
    }
}
