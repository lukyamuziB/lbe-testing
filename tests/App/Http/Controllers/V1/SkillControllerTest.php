<?php

namespace Test\App\Http\Controllers;

use App\Models\User;
use TestCase;

class SkillControllerTest extends TestCase
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
    public function testAllSuccess()
    {
        $this->get("/api/v1/skills");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertCount(50, $response->data);
    }

    /**
     * Test get user skills success
     *
     * @return void
     */
    public function testGetSuccess()
    {
        $this->get("/api/v1/skills/1");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("name", $response);
    }

    /**
     * Test get user skills failure
     *
     * @return void
     */
    public function testGetFailure()
    {
        $this->get("/api/v1/skills/90");

        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertEmpty($response);
    }

    /**
     * Test to create new skill
     *
     * @return void
     */
    public function testAddSuccess()
    {
        $this->post("/api/v1/skills", ["name" => "PHPUnit"]);

        $this->assertResponseStatus(201);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("data", $response);
        $this->assertEquals("PHPUnit", $response->data->skill->name);
        $this->assertEquals("Skill was successfully created", $response->data->message);
    }

    /**
     * Test for creating new skill failure
     *
     * @return void
     */
    public function testAddFailure()
    {
        //Duplicate Skill
        $this->post("/api/v1/skills", ["name" => "VBASIC"]);
        $this->post("/api/v1/skills", ["name" => "VBASIC"]);

        $this->assertResponseStatus(409);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Skill already exists", $response->message);

        //Invalid skill input
        $this->post("/api/v1/skills", ["name" => ""]);

        $this->assertResponseStatus(422);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("The name field is required.", $response->name[0]);
    }

    /**
     * Test that a skill can be deleted
     *
     * @return void
     */
    public function testRemoveSuccess()
    {
        $this->post("/api/v1/skills", ["name" => "Fotran"]);
        $this->delete("/api/v1/skills/51");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Skill deleted", $response->data);
    }

    /**
     * Test skill delete failure
     *
     * @return void
     */
    public function testRemoveFailure()
    {
        $this->delete("/api/v1/skills/7");

        $this->assertResponseStatus(403);
        $response = json_decode($this->response->getContent());
        $this->assertEquals(
            "Skill is currently in use",
            $response->data
        );
    }

    /**
     * Test that a skill can be updated
     *
     * @return void
     */
    public function testPutSuccess()
    {
        $this->put("/api/v1/skills/50", ["name" => "Visual Basic"]);

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("data", $response);
        $this->assertEquals("Visual Basic", $response->data->skill->name);
        $this->assertEquals(
            "Skill was successfully modified",
            $response->data->message
        );
    }

    /**
     * Test skill update failure
     *
     * @return void
     */
    public function testPutFailure()
    {
        $this->put("/api/v1/skills/4", ["name" => " "]);

        $this->assertResponseStatus(422);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("The name field is required.", $response->name[0]);
    }
}
