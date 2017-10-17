<?php

namespace Test\App\Http\Controllers;

use App\Models\User;
use Mockery\Exception;
use TestCase;

/**
 * Test class for report controller
 */
class ReportControllerTest extends TestCase
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
     * Test that a user that is an admin can get all reports
     *
     * @return void
     */
    public function testAllSuccess()
    {
        $this->get("/api/v1/reports");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        foreach ($response->skillsCount as $skill) {
            $this->assertNotEmpty($skill->name);
            $this->assertNotEmpty($skill->count);
            $this->assertObjectHasAttribute('open', $skill->count);
            $this->assertNotEmpty($skill->count->open);
        }
    }
    
    /**
     * Test that a user that is an admin can get all unmatched reqests
     *
     * @return void
     */
    public function testGetUnmatchedRequestsSuccess()
    {
        $this->get("/api/v1/reports/unmatched-requests");
        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertTrue(is_array($response->requests));
        $this->assertEquals(10, count($response->requests));
        $this->assertEquals(10, $response->pagination->totalCount);
    }
    
    /**
     * Test to check all status counts are accurate
     */
    public function testGetAllStatusRequestsCounts()
    {
        $this->get("/api/v1/reports");

        $response = json_decode($this->response->getContent());
        
        $this->assertEquals(20, $response->totalRequests);
        $this->assertEquals(10, $response->totalOpenRequests);
        $this->assertEquals(10, $response->totalMatchedRequests);
        $this->assertEquals(0, $response->totalCancelledRequests);
        $this->assertEquals(0, $response->totalCompletedRequests);
    }

    /**
     * Test to generate report with average time to match
     */
    public function testAllSuccessAverageTimeToMatch()
    {
        $this->get("/api/v1/reports");

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response->skillsCount);

        foreach ($response->skillsCount as $skill) {
            $this->assertNotEmpty($skill->name);
            $this->assertNotEmpty($skill->count);
            $this->assertObjectHasAttribute('open', $skill->count);
            $this->assertNotEmpty($skill->count->open);
        }
        $this->assertLessThanOrEqual(7, $response->averageTimeToMatch);
    }


    /**
     * Test to generate reports with all sessions completed
     */
    public function testAllSuccessSessionsCompleted()
    {
        $this->get("/api/v1/reports");

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response->skillsCount);

        foreach ($response->skillsCount as $skill) {
            $this->assertNotEmpty($skill->name);
            $this->assertNotEmpty($skill->count);
            $this->assertObjectHasAttribute('open', $skill->count);
            $this->assertNotEmpty($skill->count->open);
        }

        $this->assertEquals(4, $response->sessionsCompleted);
    }

    /**
     * Test to generate report with all query and filter included
     */
    public function testAllSuccessIncludeAll()
    {
        $this->get(
            "/api/v1/reports"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response->skillsCount);

        foreach ($response->skillsCount as $skill) {
            $this->assertNotEmpty($skill->name);
            $this->assertNotEmpty($skill->count);
            $this->assertObjectHasAttribute('open', $skill->count);
            $this->assertNotEmpty($skill->count->open);
        }
        $this->assertEquals(20, $response->totalRequests);
    }

    /**
     * Test that a user that is not an admin cannot get reports
     *
     * @return void
     */
    public function testAllFailureNotAdmin()
    {
        //Non admin user
        $this->be(
            factory(User::class)->make(
                [
                    "name" => "Adebayo Adesanya",
                    "email" => "adebayo.adesanya@andela.com",
                    "slack_id" => "C63LPE124",
                    "firstname" => "Adebayo",
                    "lastname" => "Adesanya",
                    "role" => "Fellow"
                ]
            )
        );

        $this->get("/api/v1/reports");

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(403);
        $this->assertEquals(
            "you do not have permission to perform this action",
            $response->message
        );
    }
}
