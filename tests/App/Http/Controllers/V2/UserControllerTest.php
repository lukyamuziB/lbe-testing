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
                    "roles" => ["LENKEN_ADMIN"],
                    "firstname" => "Adebayo",
                    "lastname" => "Adesanya",
                ]
            )
        );
    }

    /**
     * Creates a new user
     *
     * @param string $identifier - user's id
     * @param array  $roles      - user's roles
     */
    private function makeUser($identifier, $roles = ["Fellow"])
    {
        $this->be(
            factory(User::class)->make(
                [
                    "uid" => $identifier,
                    "name" => "Daniel Atebije",
                    "email" => "daniel.atebije@andela.com",
                    "roles" => $roles,
                    "slack_handle"=> "@danny",
                    "firstname" => "Daniel",
                    "lastname" => "Atebije",
                ]
            )
        );
    }

    /**
     * Test for get user details successfully
     *
     * @return void
     */
    public function testGetUserDetailsSuccess()
    {

        $this->get("/api/v2/users/-K_nkl19N6-EGNa0W8LF?categories=rating,skills,statistics,comments");

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

        $this->assertContains("placement", $response);

        $this->assertContains("location", $response);

        $this->assertContains("rating", $response);

        $this->assertContains("skills", $response);

        $this->assertContains("comments", $response);

        $this->assertContains("statistics", $response);

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

    /**
     * Test for user search success
     *
     * @return void
     */
    public function testForNonAdminSearchUsersSuccess()
    {
        $this->makeUser("-L4g3CXhX6cPHZXTEMNE");

        $this->get("/api/v2/users/search?q=ad");
        $response =json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertNotEmpty($response);
        $this->assertAttributeContains("Adebayo Adesanya", "fullname", $response->users[0]);
    }

    /**
     * Test for user search failure
     */
    public function testForNonAdminSearchUsersEmpty()
    {
        $this->makeUser("-L4g3CXhX6cPHZXTEMNE");

        $this->get("/api/v2/users/search?q=jytr");
        $response =json_decode($this->response->getContent());
        $this->assertResponseStatus("200");
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
     * Test for get user rating details successfully
     *
     * @return void
     */
    public function testGetUserRatingDetailsSuccess()
    {
        $this->get("/api/v2/users/-K_nkl19N6-EGNa0W8LF/rating");

        $this->assertResponseStatus(200);

        $response = $this->response->getContent();

        $this->assertNotEmpty($response);

        $this->assertContains("cumulative_average", $response);
        $this->assertContains("mentee_average", $response);
        $this->assertContains("mentor_average", $response);
        $this->assertContains("rating_count", $response);
    }
  
    /**
     * Test for get user skills successfully.
     *
     * @return void
     */
    public function testGetUserSkillsSuccess()
    {
        $this->get("/api/v2/users/-K_nkl19N6-EGNa0W8LF/skills");

        $this->assertResponseStatus(200);

        $response = $this->response->getContent();

        $this->assertNotEmpty($response);

        $this->assertContains("id", $response);
        $this->assertContains("name", $response);
    }

    /**
     * Test for get user skills failure with invalid ID.
     *
     * @return void
     */
    public function testGetUserSkillsForInvalidUser()
    {
      $this->get("/api/v2/users/-K_nkl19N6-EGNa0W8LFr/skills");

      $this->assertResponseStatus(404);

      $response = json_decode($this->response->getContent());

      $this->assertEquals("User not found.", $response->message);
    }

    /**
     * Test for get user statistics successfully.
     *
     * @return void
     */
    public function testGetUserStatisticsSuccess()
    {
        $this->get("/api/v2/users/-K_nkl19N6-EGNa0W8LF/statistics");

        $this->assertResponseStatus(200);

        $response = $this->response->getContent();

        $this->assertNotEmpty($response);

        $this->assertContains("request_count", $response);
        $this->assertContains("logged_hours", $response);
        $this->assertContains("total_sessions", $response);
    }

    /**
     * Test for get user statistics failure with invalid ID.
     *
     * @return void
     */
    public function testGetUserStatisticsForInvalidUser()
    {
      $this->get("/api/v2/users/-K_nkl19N6-EGNa0W8LFr/statistics");

      $this->assertResponseStatus(404);

      $response = json_decode($this->response->getContent());

      $this->assertEquals("User not found.", $response->message);
    }

    /**
     * Test for get user comments successfully.
     *
     * @return void
     */
    public function testGetUserCommentsSuccess()
    {
        $this->get("/api/v2/users/-K_nkl19N6-EGNa0W8LF/comments");

        $this->assertResponseStatus(200);

        $response = $this->response->getContent();

        $this->assertNotEmpty($response);

        $this->assertContains("date", $response);
        $this->assertContains("comment", $response);
        $this->assertContains("commentor", $response);
        $this->assertContains("request_title", $response);
    }

    /**
     * Test for get user comments failure with invalid ID.
     *
     * @return void
     */
    public function testGetUserCommentsForInvalidUser()
    {
      $this->get("/api/v2/users/-K_nkl19N6-EGNa0W8LFr/comments");

      $this->assertResponseStatus(404);

      $response = json_decode($this->response->getContent());

      $this->assertEquals("User not found.", $response->message);
    }
}
