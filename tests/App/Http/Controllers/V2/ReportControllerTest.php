<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use App\Http\Controllers\V2\SkillController;
use TestCase;
use Carbon\Carbon;

/**
 * Test class for report controller v2
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
     * Test that a user that is an admin can get all request statistics
     *
     * @return void
     */
    public function testGetRequestsStatusStatisticsSuccess()
    {
        $this->get("/api/v2/requests/status-statistics");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertNotEmpty($response);
    }

    /**
     * Test to check all status statistics are accurate
     */
    public function testGetRequestStatusCountSuccess()
    {
        $this->get("/api/v2/requests/status-statistics");

        $response = json_decode($this->response->getContent());

        $this->assertEquals(25, $response->total);
        $this->assertEquals(12, $response->open);
        $this->assertEquals(13, $response->matched);
        $this->assertEquals(0, $response->cancelled);
        $this->assertEquals(0, $response->completed);
    }

    /**
     * Test that a user that is not an admin cannot get statistics
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

        $this->get("/api/v2/requests/status-statistics");

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(403);
        $this->assertEquals(
            "You do not have permission to perform this action.",
            $response->message
        );
    }

    /**
     * Test to check if the date range filter is accurate.
     */
    public function testGetRequestDateRangeStatusCountSuccess()
    {
        $this->get("/api/v2/requests/status-statistics?start_date=01-03-2017&end_date=01-02-2018");
        $response = json_decode($this->response->getContent());

        $this->assertEquals(11, $response->total);
        $this->assertEquals(4, $response->open);
        $this->assertEquals(7, $response->matched);
        $this->assertEquals(0, $response->cancelled);
        $this->assertEquals(0, $response->completed);
    }
}
