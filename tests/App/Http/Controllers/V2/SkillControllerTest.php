<?php

namespace Test\App\Http\Controllers\v2;

use App\Models\User;

class SkillControllerTest extends \TestCase
{
    /**
     * SetUp before each test
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->be(
            factory(User::class)->make(
                [
                    "uid" => "-KXGy1MT1oimjQgFim7u",
                    "name" => "Adebayo Adesanya",
                    "email" => "adebayo.adesanya@andela.com",
                    "role" => "Admin",
                    "firstname" => "Adebayo",
                    "lastname" => "Adesanya",
                ]
            )
        );
    }

    /**
     * Test get all skill success
     *
     * @return void
     */
    public function testAllSkillsSuccess()
    {
        $this->get("/api/v2/skills");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertCount(50, $response);
    }
}
