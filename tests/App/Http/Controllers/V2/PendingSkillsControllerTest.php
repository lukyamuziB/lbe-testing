<?php

namespace Test\App\Http\Controllers\V2;

use App\Http\Controllers\V2\PendingSkillsController;
use App\Models\User;
use Mockery as m;
use TestCase;
use App\Repositories\pendingSkillsRepository;

/**
 * Test class for report controller v2
 */
class PendingSkillsControllerTest extends TestCase
{
    private $mentorsIds = [
        "-L4g35ttuyfK5kpzyocv",
        "-KesEogCwjq6lkOzKmLI"
    ];
    private $pendingSkillsRepository;
    private $pendingSkillsController;

    private function setupMock()
    {
        $this->pendingSkillsRepository = m::mock(PendingSkillsRepository::class);

        return new PendingSkillsController($this->pendingSkillsRepository);
    }

    /**
     * Create users for each test case
     *
     * @return null
     */
    public function setUp()
    {
        parent::setUp();
        $this->be(
            factory(User::class)->make(
                [
                    "name" => "Adebayo Adesanya",
                    "email" => "adebayo.adesanya@andela.com",
                    "slack_id" => "C63LPE124",
                    "firstname" => "Adebayo",
                    "lastname" => "Adesanya",
                    "roles" => ["LENKEN_ADMIN"],
                ]
            )
        );

        $this->pendingSkillsController = $this->setupMock();
    }

    /**
     * Test that a pending skill is added
     */
    public function testAddPendingSkillSuccess()
    {
        $this->post("api/v2/skills/pending-skills", ["userId"=>$this->mentorsIds[1], "skill"=>"flutter"]);

        $response = json_decode($this->response->getContent());

        $this->assertNotNull($response);
        $this->assertResponseStatus(201);
    }


    /**
     * Test that when empty userId and Skill is provided an exception is thrown
     */
    public function testAddPendingSkillFailureForInvalidParameters()
    {
        $this->post("api/v2/skills/pending-skills", ["userId"=>"", "skill"=>""]);

        $response = json_decode($this->response->getContent());

        $this->assertNotNull($response);
        $this->assertResponseStatus(400);
    }

    /**
     * Test that an existing pending skill is added to a user
     */
    public function testAddPendingSkillToUserSuccess()
    {
        $this->post("api/v2/skills/pending-skills", ["userId"=>$this->mentorsIds[0], "skill"=>"Ruby"]);

        $response = json_decode($this->response->getContent());
        $this->assertNotNull($response);
        $this->assertResponseStatus(201);
    }

    /**
     * Test that all pending skill are returned
     */
    public function testGetAllPendingSkills()
    {
        $this->get("api/v2/skills/pending-skills");

        $response = json_decode($this->response->getContent());

        $this->assertNotNull($response);
        $this->assertResponseOk();
    }

    /**
     * Test that all users with pending skills are returned
     */
    public function testGetAllUsersSuccess()
    {
        $this->get("api/v2/skills/pending-skills/users");

        $response = json_decode($this->response->getContent());

        $this->assertNotNull($response);
        $this->assertResponseOk();
    }

    /**
     * Test that all users associated with a skill are returned
     */
    public function testGetUsersBySkillSuccess()
    {
        $this->get("api/v2/skills/pending-skills/Ruby");
        $response = json_decode($this->response->getContent());

        $this->assertNotNull($response);
        $this->assertResponseOk();
    }

    /**
     * Test that an exception is thrown when skill provided doesnot exist
     */
    public function testGetUsersBySkillFailureForInvalidSkill()
    {
        $this->get("api/v2/skills/pending-skills/AMW");
        $response = json_decode($this->response->getContent());

        $this->assertNotNull($response);
        $this->assertResponseOk();
    }

    /**
     * Test that a single user's pending skills are returned
     */
    public function testGetSingleUserSkillsSuccess()
    {
        $this->get("api/v2/skills/pending-skills/users/-L4g35ttuyfK5kpzyocv");

        $response = json_decode($this->response->getContent());

        $this->assertNotNull($response);
        $this->assertResponseOk();
    }

    /**
     * Test exception is thrown when user has no pending skills
     */
    public function testGetSingleUserSkillsFailureForInvalidUser()
    {
        $this->get("api/v2/skills/pending-skills/users/-L4g35ttuyfK5kpzyoc");

        $response = json_decode($this->response->getContent());

        $this->assertNull($response);
    }
}
