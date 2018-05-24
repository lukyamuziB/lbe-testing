<?php

namespace Test\App\Http\Controllers;

use App\Models\User;
use TestCase;
use Carbon\Carbon;

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
                    "roles" => ["LENKEN_ADMIN"]
                ]
            )
        );
    }

    public function testGetInactiveMentorshipsReportSuccess()
    {
        $today = Carbon::now();
        $startDate = $today->subWeek(2)->toDateString();
        $this->get("/api/v1/reports/inactive-mentorships?start_date=".$startDate);
        $response = json_decode($this->response->getContent(), true);
        $this->assertEquals(17, $response[1]["count"]);
    }

    public function testGetInactiveMentorshipsReportFailureNotAdmin()
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

        $today = Carbon::now();
        $startDate = $today->subWeek(3)->toDateString();
        $this->get("/api/v1/reports/inactive-mentorships?start_date=".$startDate);

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(403);
        $this->assertEquals(
            "You do not have permission to perform this action.",
            $response->message
        );
    }

    public function testGetInactiveMentorshipsReportFailureForNoStartDate()
    {
        $this->get("/api/v1/reports/inactive-mentorships?start_date=");

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(400);
        $this->assertEquals(
            "Start date is required to get report.",
            $response->message
        );
    }
}
