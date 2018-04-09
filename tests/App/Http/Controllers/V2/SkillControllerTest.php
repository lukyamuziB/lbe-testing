<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use App\Models\Skill;
use App\Http\Controllers\V2\SkillController;
use TestCase;
use Carbon\Carbon;
use App\Repositories\LastActiveRepository;

/**
 * Test class for report controller v2
 */
class SkillControllerTest extends TestCase
{
    private $mentorsIds = [
        "-L4g35ttuyfK5kpzyocv",
        "-KesEogCwjq6lkOzKmLI"
    ];
    private $mentorsDetails = [
        [
            "average_rating" => "4.9",
            "session_count" => 2,
            "user_id" => "-L4g35ttuyfK5kpzyocv",
            "name" => "Ekundayo Abiona",
            "picture" => "dayo_picture.jpg",
        ]
    ];
    private function setupMock()
    {
        $lastActiveMock = $this->createMock(LastActiveRepository::class);
        return new SkillController($lastActiveMock);
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
                    "role" => "Admin"
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

    /**
     * Test get all skill with trashed skills success
     *
     * @return void
     */
    public function testAllSkillsWithTrashedSuccess()
    {
        $this->get("/api/v2/skills?isTrashed=true");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertCount(50, $response);
    }


    /**
     * Test to return skill status count for all locations
     *
     * @return void
     */
    public function testGetSkillStatusCountForAllLocations()
    {
        $this->get(
            "/api/v2/skill/status-report"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(2, $response);

        foreach ($response as $skill) {
            $this->assertNotEmpty($skill->name);
            $this->assertNotEmpty($skill->count);
            $this->assertObjectHasAttribute('open', $skill->count);
            $this->assertNotEmpty($skill->count->open);
        }
    }

     /**
      * Test to return skill status count for start and end date query parameters
      *
      * @return void
      */
    public function testGetSkillStatusCountForStartAndEndDate()
    {
        $this->get(
            "/api/v2/skill/status-report?start_date=2017-11-13&end_date=2017-12-09"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(0, $response);
    }

     /**
      * Test to return skill status count for when the start date
      * is more than the end date
      *
      * @return void
      */
    public function testGetSkillStatusCountForStartDateMoreThanEndDate()
    {
        $this->get(
            "/api/v2/skill/status-report?start_date=2017-11-13&end_date=2017-10-13"
        );

        $response = json_decode($this->response->getContent());
        $errorResponse = (object) [
            "message" => "start_date cannot be greater than end_date."
        ];
        $this->assertEquals($response, $errorResponse);
    }

    /**
     * Test private method getEachSkillStatusCount
     *
     * @return void
     */
    public function testGetEachSkillStatusCount()
    {
        $method = new \ReflectionMethod('App\Http\Controllers\V2\SkillController', 'getEachSkillStatusCount');
        $method->setAccessible(true);

        $skillObject = $this->setupMock();

        $request = [
            (object) [
                "requestSkills" => [
                    (object) [
                        "skill" => (object) [
                            "id" => 15,
                            "type" => "primary",
                            "name" => "VALUE"
                        ]
                    ]
                ],
                "status" => (object) [
                    "name" => "Open"
                ]
            ]
        ];

        $result = [
            [
                "name" => "VALUE",
                "count" => [
                    "Open" => 1
                ]
            ]
        ];

        $this->assertEquals($method->invoke($skillObject, $request), $result);
    }

    /**
     * Test that a skill can be disabled
     *
     * @return void
     */
    public function testUpdateSkillStatusSuccess()
    {
        $this->patch("/api/v2/skills/3/update-status", ["status" => "inactive"]);

        $this->assertResponseOk();
    }

    /**
     * Test skill update failure
     *
     * @return void
     */
    public function testUpdateSkillStatusFailureForDisabledSkill()
    {
        $this->patch("/api/v2/skills/400/update-status", ["status" => "inactive"]);

        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Skill not found.", $response->message);
    }

    /**
     * Test skill update failure
     *
     * @return void
     */
    public function testUpdateSkillStatusForInvalidParameters()
    {
        $this->patch("/api/v2/skills/30/update-status", ["status" => null]);

        $this->assertResponseStatus(400);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Invalid parameters.", $response->message);
    }

    /**
     * Test to create skill successfully
     *
     * @return void
     */
    public function testAddSkillSuccess()
    {
        $this->post("/api/v2/skills", ["name" => "AI"]);

        $this->assertResponseStatus(201);
        $skill = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("name", $skill);
        $this->assertEquals("AI", $skill->name);
    }

    /**
     * Test for creating duplicate skill
     *
     * @return void
     */
    public function testAddSkillFailureForDuplicates()
    {
        $skill = factory(Skill::class)->create(
            [
            'name' => 'AI'
            ]
        );
        $this->post("/api/v2/skills", ["name" => "AI"]);
        $this->assertResponseStatus(409);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Skill already exists.", $response->message);
    }

    /**
     * Test for creating skill with an empty field
     *
     * @return void
     */
    public function testAddSkillSkillFailureForEmptyNameField()
    {
        $this->post("/api/v2/skills", ["name" => ""]);

        $this->assertResponseStatus(422);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("The name field is required.", $response->name[0]);
    }

    /**
     * Test to return skill requests
     */
    public function testGetSkillTopMentorsSuccess()
    {
        $this->get(
            "/api/v2/skills/1/mentors"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertNotEmpty($response);
        $this->assertObjectHasAttribute("mentors", $response->skill);
        $this->assertLessThan(5, sizeof($response->skill->mentors));
    }

    /**
     * Test to return error message if there skill id is invalid
     */
    public function testGetSkillTopMentorsFailure()
    {
        $this->get(
            "/api/v2/skills/1hsdxh1/mentors"
        );

        $response = json_decode($this->response->getContent());
        $errorResponse = (object) [
            "message" => "Invalid parameter."
        ];
        $this->assertEquals($response, $errorResponse);
    }

    /**
     * Test to return mentors by skill
     */
    public function testGetSkillMentorsSuccess()
    {
        $this->get(
            "/api/v2/skills/18/mentors"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertNotEmpty($response);
        $this->assertObjectHasAttribute("mentorships_count", $response->skill->mentors[0]);
        $this->assertObjectHasAttribute("last_active", $response->skill->mentors[0]);
    }

    /**
     * Test to ensure that the last active time
     * is appended to an array of mentors details
     */
    public function testAppendMentorsLastActiveSuccess()
    {
        $method = new \ReflectionMethod('App\Http\Controllers\V2\SkillController', 'appendMentorsLastActive');
        $method->setAccessible(true);

        $skillObject = $this->setupMock();
        $actualResult = $method->invokeArgs(
            $skillObject,
            array(&$this->mentorsDetails)
        );

        $this->assertObjectHasAttribute("last_active", (object)$actualResult[0]);
    }

    /**
     * Test to ensure that the mentorships count is
     * appended to an array of mentors details
     */
    public function testAppendMentorshipsCountSuccess()
    {
        $method = new \ReflectionMethod('App\Http\Controllers\V2\SkillController', 'appendMentorshipsCount');
        $method->setAccessible(true);

        $skillObject = $this->setupMock();
        $actualResult = $method->invokeArgs(
            $skillObject,
            array($this->mentorsDetails, &$this->mentorsDetails)
        );

        $this->assertObjectHasAttribute("mentorships_count", (object)$actualResult[0]);
    }
}
