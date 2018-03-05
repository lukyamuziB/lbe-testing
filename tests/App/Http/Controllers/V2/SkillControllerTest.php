<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use App\Http\Controllers\V2\SkillController;
use TestCase;
use Carbon\Carbon;

/**
 * Test class for report controller v2
 */
class SkillControllerTest extends TestCase
{
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
     * Test to return skill status count for all locations
     */
    public function testGetSkillStatusCountForAllLocations()
    {
        $this->get(
            "/api/v2/skill/status-report"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response);

        foreach ($response as $skill) {
            $this->assertNotEmpty($skill->name);
            $this->assertNotEmpty($skill->count);
            $this->assertObjectHasAttribute('open', $skill->count);
            $this->assertNotEmpty($skill->count->open);
        }
    }

     /**
     * Test to return skill status count for start and end date query parameters
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
     * Test to return skill status count for when the start date is more than the end date
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
     */
    public function testGetEachSkillStatusCount()
    {
        $method = new \ReflectionMethod('App\Http\Controllers\V2\SkillController', 'getEachSkillStatusCount');
        $method->setAccessible(true);

        $skillObject = new SkillController();

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
}
