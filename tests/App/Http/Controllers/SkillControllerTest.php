<?php

namespace Test\App\Http\Controllers;

use App\Models\User;
use TestCase;

class SkillControllerTest extends TestCase
{
    public function setUp() {
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
     * Happy test for getting all skills
     */
    public function testAll()
    {
        $this->get("/api/v1/skills");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->data);

        $this->assertCount(50, $response->data);
    }

    /**
     * Happy test for getting skill by id
     */
    public function testSkillById()
    {
        $this->get("/api/v1/skills/1");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('name', $response);

        $this->assertNotEmpty($response->name);
    }
}
