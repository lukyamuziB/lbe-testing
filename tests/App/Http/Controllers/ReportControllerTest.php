<?php

namespace Test\App\Http\Controllers;

use App\Models\User;
use TestCase;

/**
 * Test class for report controller
 *
 */
class ReportControllerTest extends TestCase
{
    private $user = [
        "name" => "Adebayo Adesanya",
        "email" => "adebayo.adesanya@andela.com",
        "slack_id" => "C63LPE124",
        "firstname" => "Adebayo",
        "lastname" => "Adesanya"
    ];

    /**
     * Test that a user that is an admin can get all reports
     *
     * @return void
     */
    public function testAllSuccess()
    {
        //Admin user
        $this->be(
            factory(User::class)->make(
                array_merge($this->user, [ "role" => "Admin" ])
            )
        );

        $this->get("/api/v1/reports");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        foreach ($response->data->skills_count as $skill) {
            $this->assertNotEmpty($skill->count);
        }

        //Generate reports with all requests
        $this->get("/api/v1/reports?include=totalRequests");

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response->data->skills_count);

        foreach ($response->data->skills_count as $skill) {
            $this->assertNotEmpty($skill->name);
        }
        $this->assertEquals(20, $response->data->totalRequests);


        //Generate report with total request matched filter
        $this->get("/api/v1/reports?include=totalRequestsMatched");
 
        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response->data->skills_count);

        foreach ($response->data->skills_count as $skill) {
            $this->assertNotEmpty($skill->name);
        }
        $this->assertEquals(10, $response->data->totalRequestsMatched);


        //Generate report with average time to match filter
        $this->get("/api/v1/reports?include=averageTimeToMatch");

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response->data->skills_count);
        
        foreach ($response->data->skills_count as $skill) {
            $this->assertNotEmpty($skill->name);
        }
        $this->assertEquals(0, $response->data->averageTimeToMatch);


        //Generate report for session completed
        $this->get("/api/v1/reports?include=sessionsCompleted");

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response->data->skills_count);

        foreach ($response->data->skills_count as $skill) {
            $this->assertNotEmpty($skill->name);
        }
        $this->assertEquals(10, $response->data->sessionsCompleted);


        //Generate all reports
        $this->get(
            "/api/v1/reports?include=totalRequests,totalRequestsMatched,averageTimeToMatch,sessionsCompleted"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertCount(1, $response->data->skills_count);

        foreach ($response->data->skills_count as $skill) {
            $this->assertNotEmpty($skill->name);
        }
        $this->assertEquals(20, $response->data->totalRequests);
    }

    /**
     * Test that a user that is not an admin cannot get reports
     *
     * @return void
     */
    public function testAllFailure()
    {
        //Non admin user
        $this->be(
            factory(User::class)->make(
                array_merge($this->user, [ "role" => "Fellow" ])
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
