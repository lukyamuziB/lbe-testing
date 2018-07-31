<?php

namespace Tests\App\Repositories;

use App\Repositories\PendingSkillsRepository;
use Carbon\Carbon;
use Mockery as m;
use TestCase;

class PendingSkillsRepositoryTest extends TestCase
{
    private function setupMock()
    {
        $this->pendingSkillsRepositoryMock = m::mock(PendingSkillsRepository::class);

        return new PendingSkillsRepository($this->pendingSkillsRepositoryMock);
    }

    /**
     * Create a mock for the pending skills repository
     */
    public function setUp()
    {
        $this->pendingSkillsRepositoryMock = m::mock(PendingSkillsRepository::class);

        $this->pendingSkillsRepository = $this->setupMock();
    }


    /**
     * Test add pending skills success
     */
    public function testAddPendingSkills()
    {
        $this->pendingSkillsRepositoryMock->shouldReceive("add")->once()->andReturn(
            [
                "dateRequested"=> Carbon::now()->toFormattedDateString(),
                "name"=> "flash",
                "userId" => "-KesEogCwjq6lkOzKmLI",
                "status" => "pending"]
        );

        $response = $this->pendingSkillsRepository->add("flash", "-KesEogCwjq6lkOzKmLI");

        $this->assertEquals(((object)[
            "dateRequested"=> Carbon::now()->toFormattedDateString(),
            "name"=> "flash",
            "userId" => "-KesEogCwjq6lkOzKmLI",
            "status" => "pending"]), ($response));
    }

    public function testAddPendingSkillToUser()
    {
        $this->pendingSkillsRepositoryMock->shouldReceive("add")->once()->andReturn(
            [
                    "dateRequested"=> Carbon::now()->toFormattedDateString(),
                    "name"=> "flash",
                    "userId" => "-KesEogCwjq6lkOzKmLI",
                    "status" => "pending"]
        );

        $this->pendingSkillsRepositoryMock->shouldReceive("addSkillToUser")->once()->andReturn(
            [
                    "dateRequested"=> Carbon::now()->toFormattedDateString(),
                    "userId" => "-KesEogCwjq6lkOzKmLI",
                    "status" => "pending",
                    "name"=> "flash"]
        );

        $response = $this->pendingSkillsRepository->addSkillToUser("flash", "-KesEogCwjq6lkOzKmLI");

        $this->assertEquals(((object)[
                "dateRequested"=> Carbon::now()->toFormattedDateString(),
                "name"=> "flash",
                "userId" => "-KesEogCwjq6lkOzKmLI",
                "status" => "pending"]), ($response));
    }

    /**
     * Test add existing skill to user
     */
    public function testGetAllUsers()
    {
        $this->pendingSkillsRepositoryMock->shouldReceive("getAllUsers")->once()->andReturn(array());

        $response = $this->pendingSkillsRepository->getAllUsers();

        $this->assertEquals("object", gettype($response));
    }

    /**
     * Test get all users associated with a given skill
     */
    public function testGetById()
    {
        $this->pendingSkillsRepositoryMock->shouldReceive("getById")->once()->andReturn(array());

        $response = $this->pendingSkillsRepository->getById("Ruby");

        $this->assertEquals("object", gettype($response));
    }

    /**
     * Test get a user's requested skills
     */
    public function testGetUsersSkills()
    {
        $this->pendingSkillsRepositoryMock->shouldReceive("getUserSkills")->once()->andReturn(array());

        $response = $this->pendingSkillsRepository->getUserSkills("-ksssss");

        $this->assertEquals("object", gettype($response));
    }

    /**
     * Test get all pending skills
     */
    public function testGetAllPendingSkills()
    {
        $this->pendingSkillsRepositoryMock->shouldReceive("getAll")->once()->andReturn(array());

        $response = $this->pendingSkillsRepositoryMock->getAll();

        $this->assertEquals("array", gettype($response));

        //delete all credentials files
        unlink("./credentials.json");
        unlink("./firebase-credentials.json");
    }

    public function tearDown()
    {
    }
}
