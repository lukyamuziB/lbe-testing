<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;

class UserControllerTest extends \TestCase
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
     * Test to add new user skill
     *
     * @return void
     */
    public function testAddUserSkillSuccess()
    {
        $this->post("/api/v2/users/-KXGy1MT1oimjQgFim7u/skills", ["skill_id" => "50"]);

        $this->assertResponseStatus(201);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("User skill added.", $response->message);
    }

    /**
     * Test for adding new user skill failure
     *
     * @return void
     */
    public function testAddUserSkillFailureForDuplicateUserSkills()
    {
        $this->post("/api/v2/users/-KXGy1MT1oimjQgFim7u/skills", ["skill_id" => "20"]);
        $this->post("/api/v2/users/-KXGy1MT1oimjQgFim7u/skills", ["skill_id" => "20"]);

        $this->assertResponseStatus(409);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("User skill already exists.", $response->message);
    }

    /**
     * Test for adding a user skill for a skill that does not exist
     *
     * @return void
     */
    public function testAddUserSkillFailureForInvalidSkill()
    {
        $this->post("/api/v2/users/-KXGy1MT1oimjQgFim7u/skills", ["skill_id" => "120"]);

        $this->assertResponseStatus(404);

        $response = json_decode($this->response->getContent());
        $this->assertEquals("Skill does not exist.", $response->message);
    }


    /**
     * Test that a user skill can be deleted
     *
     * @return void
     */
    public function testDeleteUserSkillSuccess()
    {
        $this->post("/api/v2/users/-KXGy1MT1oimjQgFim7u/skills", ["skill_id" => "21"]);
        $this->delete("/api/v2/users/-KXGy1MT1oimjQgFim7u/skills/21");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertEquals("User skill deleted.", $response->message);
    }

    /**
     * Test user skill delete failure
     *
     * @return void
     */
    public function testDeleteUserSkillFailureForInvalidUserSkill()
    {
        $this->delete("/api/v2/users/-KXGy1MT1oimjQgFim7u/skills/300");

        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("User skill does not exist.", $response->message);
    }

    /**
     * Test for get user details successfully
     *
     * @return void
     */
    public function testGetUserDetailsSuccess()
    {
        
        $this->get("/api/v2/users/-KXGy1MimjQgFim7u");

        $this->assertResponseStatus(200);
        
        $response = $this->response->getContent();
        
        $this->assertNotEmpty($response);
        
        $this->assertContains("id", $response);

        $this->assertContains("email", $response);

        $this->assertContains("name", $response);

        $this->assertContains("picture", $response);

        $this->assertContains("firstName", $response);

        $this->assertContains("cohort", $response);

        $this->assertContains("roles", $response);

        $this->assertContains("rating", $response);

        $this->assertContains("skills", $response);

        $this->assertContains("totalSessions", $response);

        $this->assertContains("requestCount", $response);

        $this->assertContains("placement", $response);

        $this->assertContains("location", $response);
    }

    /**
     * Test for get user details with invalid ID
     *
     * @return void
     */
    public function testGetUserDetailsFailure()
    {
            $this->get("/api/v2/users/-KXGy1MTimjQgFim7uh");

            $this->assertResponseStatus(404);
            
            $response = json_decode($this->response->getContent());
            
            $this->assertEquals("User not found.", $response->message);
    }

    /**
     * Test for get user details successfully
     *
     * @return void
     */
    public function testGetUsersByIdsSuccess()
    {

        $this->get("/api/v2/users?ids=-K_nkl19N6-EGNa0W8LF,-KXGy1MimjQgFim7u");

        $this->assertResponseStatus(200);

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response);

        $this->assertAttributeContains("Adebayo Adesanya", "name", $response[0]);

        $this->assertAttributeContains("Inumidun Amao", "name", $response[1]);

        $this->assertCount(2, $response);
    }
}
