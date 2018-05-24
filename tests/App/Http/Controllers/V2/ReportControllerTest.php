<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use App\Models\Status;
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
                    "roles" => ["LENKEN_ADMIN"]
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

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertNotEmpty($response);
    }

    /**
     * Test to check all status statistics are accurate
     */
    public function testGetRequestStatusCountSuccess()
    {
        $this->get("/api/v2/requests/status-statistics");

        $response = json_decode($this->response->getContent());

        $this->assertEquals(35, $response->total);
        $this->assertEquals(12, $response->open);
        $this->assertEquals(17, $response->matched);
        $this->assertEquals(0, $response->cancelled);
        $this->assertEquals(6, $response->completed);
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
                    "roles" => ["Fellow"]
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
        $statusIds = [
            Status::OPEN,
            Status::MATCHED,
            Status::CANCELLED,
            Status::COMPLETED,
        ];

        foreach ($statusIds as $statusId) {
            $this->createRequest("-KXGy1MT1oimjQgFim7u", "javascript", "-K_nkl19N6-EGNa0W8LF", $statusId, Carbon::now());
        }

        $startAndEndDate = Carbon::now()->format("d-m-Y");

        $this->get("/api/v2/requests/status-statistics?start_date=".$startAndEndDate."&end_date=".$startAndEndDate);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals(1, $response->open);
        $this->assertEquals(1, $response->matched);
        $this->assertEquals(1, $response->cancelled);
        $this->assertEquals(1, $response->completed);
    }
}
