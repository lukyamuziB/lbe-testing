<?php

namespace Test\App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Test\Mocks\GoogleCalendarClientMock;
use App\Models\User;
use App\Models\Request;
use TestCase;

class RequestControllerTest extends TestCase
{
    private $requests = [
        "request_empty_description" => [
            "title" => "Angular 2 and PHP",
            "description" => "",
            "duration" => "3",
            "pairing" => [
                "start_time" => "01:00",
                "end_time" => "03:00",
                "days" => [
                    "monday"
                ],
                "timezone" => "WAT"
            ],
            "primary" => ["1"],
            "secondary" => ["1"],
            "location" => "Lagos"
        ],
        "request_empty_start_time" => [
            "title" => "Angular 2 and PHP",
            "description" => "I need a mentor to help me level up in Angular 2 and PHP",
            "duration" => "3",
            "pairing" => [
            "start_time" => "",
            "end_time" => "03:00",
            "days" => [
            "monday"
            ],
            "timezone" => "WAT"
            ],
            "primary" => ["1"],
            "secondary" => ["1"],
            "location" => "Lagos"
        ],
        "request_empty_end_time" => [
            "title" => "Angular 2 and PHP",
            "description" => "I need a mentor to help me level up in Angular 2 and PHP",
            "duration" => "3",
            "pairing" => [
            "start_time" => "01:00",
            "end_time" => "",
            "days" => [
            "monday"
            ],
            "timezone" => "WAT"
            ],
            "primary" => ["1"],
            "secondary" => ["1"],
            "location" => "Lagos"
        ],
        "request_empty_days" => [
            "title" => "Angular 2 and PHP",
            "description" => "I need a mentor to help me level up in Angular 2 and PHP",
            "duration" => "3",
            "pairing" => [
            "start_time" => "01:00",
            "end_time" => "03:00",
            "days" => [],
            "timezone" => "WAT"
            ],
            "primary" => ["1"],
            "secondary" => ["1"],
            "location" => "Lagos"
        ],
        "valid_request" => [
            "title" => "Angular 2 and PHP",
            "description" => "I need a mentor to help me level up in Angular 2 and PHP",
            "duration" => "3",
            "pairing" => [
                "start_time" => "01:00",
                "end_time" => "03:00",
                "days" => [
                    "monday"
                ],
                "timezone" => "WAT"
            ],
            "primary" => ["1"],
            "secondary" => ["1"],
            "location" => "Lagos",
            "status_id" => 1,
            "request_type_id" => 2,
        ]
    ];

    private $empty_title_request = [
        "title" => "",
        "description" => "I need a mentor to help me level up in Angular 2 and PHP",
        "duration" => "3",
        "pairing" => [
            "start_time" => "01:00",
            "end_time" => "03:00",
            "days" => [
                "monday"
            ],
            "timezone" => "WAT"
        ],
        "primary" => ["1"],
        "secondary" => ["1"],
        "location" => "Lagos"
    ];

    private function makeUser($id, $role = "Fellow")
    {
        $this->be(
            factory(User::class)->make(
                [
                    "uid" => $id,
                    "name" => "Inumidun Amao",
                    "email" => "inumidun.amao@andela.com",
                    "role" => $role,
                    "slack_handle"=> "@amao",
                    "firstname" => "Inumidun",
                    "lastname" => "Amao",
                ]
            )
        );
    }

    private $google_calendar_client_mock;

    public function setUp()
    {
        parent::setUp();

        Mail::fake();

        $this->google_calendar_client_mock = new GoogleCalendarClientMock();

        $this->app->instance("App\Clients\GoogleCalendarClient", $this->google_calendar_client_mock);

        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
    }

    /*
     * Test for getting all requests from the database
     */
    public function testAllSuccess()
    {
        $this->get("/api/v1/requests");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->pagination);

        $this->assertEquals(35, $response->pagination->totalCount);

        $this->assertEquals(20, $response->pagination->pageSize);

        $this->assertNotEmpty($response->requests);

        $this->assertCount(20, $response->requests);
    }

    /**
     *  Test for getting requests with a set limit
     */
    public function testAllLimitSuccess()
    {
        $this->get("/api/v1/requests?limit=10");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->pagination);

        $this->assertEquals(35, $response->pagination->totalCount);

        $this->assertEquals(10, $response->pagination->pageSize);

        $this->assertNotEmpty($response->requests);

        $this->assertCount(10, $response->requests);
    }

    /*
     * Test for getting a single request
     */
    public function testGetSuccess()
    {
        $this->get("/api/v1/requests/4");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->data);

        $this->assertNotEmpty($response->data->mentee_email);

        $this->assertEquals(4, $response->data->id);
    }

    /*
     * Test that a user cannot get a request that does not exist
     */
    public function testGetFailureNotFound()
    {
        $this->get("/api/v1/requests/49");

        $this->assertResponseStatus(404);

        $response = json_decode($this->response->getContent());

        $this->assertEquals("The request was not found", $response->message);
    }

    /**
     * Test should create a request with valid input
     */
    public function testAddSuccess()
    {
        $this->post("/api/v1/requests", $this->requests["valid_request"]);

        $this->assertResponseStatus(201);

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(
            $this->requests["valid_request"]["title"],
            $response->title
        );

        $this->assertEquals(
            $this->requests["valid_request"]["description"],
            $response->description
        );

        $this->assertEquals(
            $this->requests["valid_request"]["duration"],
            $response->duration
        );

        $this->assertEquals(
            $this->requests["valid_request"]["pairing"]["start_time"],
            $response->pairing->start_time
        );

        $this->assertEquals(
            $this->requests["valid_request"]["pairing"]["end_time"],
            $response->pairing->end_time
        );

        $this->assertEquals(
            $this->requests["valid_request"]["pairing"]["days"],
            $response->pairing->days
        );

        $this->assertEquals(
            $this->requests["valid_request"]["pairing"]["timezone"],
            $response->pairing->timezone
        );

        $this->assertEquals(
            $this->requests["valid_request"]["location"],
            $response->location
        );

        $this->assertEquals(1, $response->status_id);

        $this->assertEquals(36, $response->id);
    }


    /**
     * Test should not create a request with invalid details
     */
    public function testAddFailureEmptyTitle()
    {
        $this->post("/api/v1/requests", $this->empty_title_request);

        $this->assertResponseStatus(422);

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->title);

        $this->assertEquals("The title field is required.", $response->title[0]);
    }

    /**
     * Test should fail when user does not enter a description
     */
    public function testAddFailureEmptyDescription()
    {
        $this->post("/api/v1/requests", $this->requests["request_empty_description"]);

        $this->assertResponseStatus(422);

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->description);

        $this->assertEquals(
            "The description field is required.",
            $response->description[0]
        );
    }

    /**
     * Test should fail when user does not enter a start time
     */
    public function testAddFailureInvalidStartTime()
    {
        $this->post("/api/v1/requests", $this->requests["request_empty_start_time"]);

        $this->assertResponseStatus(422);

        $response = (array)json_decode($this->response->getContent());

        $this->assertNotEmpty($response["pairing.start_time"]);

        $this->assertEquals(
            "The pairing.start time field is required.",
            $response["pairing.start_time"][0]
        );
    }

    /**
     * Test should fail when user does not enter a end time
     */
    public function testAddFailureInvalidEndTime()
    {
        $this->post("/api/v1/requests", $this->requests["request_empty_end_time"]);

        $this->assertResponseStatus(422);

        $response = (array)json_decode($this->response->getContent());

        $this->assertNotEmpty($response["pairing.end_time"]);

        $this->assertEquals(
            "The pairing.end time field is required.",
            $response["pairing.end_time"][0]
        );
    }

    /**
     * Test should fail when user does not enters empty dats
     */
    public function testAddFailureEmptyDays()
    {
        $this->post("/api/v1/requests", $this->requests["request_empty_days"]);

        $this->assertResponseStatus(422);

        $response = (array)json_decode($this->response->getContent());

        $this->assertNotEmpty($response["pairing.days"]);

        $this->assertEquals(
            "The pairing.days field is required.",
            $response["pairing.days"][0]
        );
    }

    /**
     * Test for successfully updating a request
     */
    public function testUpdateSuccess()
    {
        $this->put("/api/v1/requests/13", $this->requests["valid_request"]);

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(
            $this->requests["valid_request"]["title"],
            $response->title
        );

        $this->assertEquals(
            $this->requests["valid_request"]["description"],
            $response->description
        );

        $this->assertEquals(
            $this->requests["valid_request"]["duration"],
            $response->duration
        );

        $this->assertEquals(
            $this->requests["valid_request"]["pairing"]["start_time"],
            $response->pairing->start_time
        );

        $this->assertEquals(
            $this->requests["valid_request"]["pairing"]["end_time"],
            $response->pairing->end_time
        );

        $this->assertEquals(
            $this->requests["valid_request"]["pairing"]["days"],
            $response->pairing->days
        );

        $this->assertEquals(
            $this->requests["valid_request"]["pairing"]["timezone"],
            $response->pairing->timezone
        );

        $this->assertEquals(
            $this->requests["valid_request"]["location"],
            $response->location
        );

        $this->assertEquals(1, $response->status_id);

        $this->assertEquals(13, $response->id);
    }

    /**
     * Test should check that a user cannot update a request that does not exist
     */
    public function testUpdateFailureNotFound()
    {
        // Fail to update requests that do not exists
        $this->put("/api/v1/requests/49", $this->requests["valid_request"]);

        $this->assertResponseStatus(404);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "The specified mentor request was not found",
            $response->message
        );
    }

    /**
     * Test should fail when trying to update a request using invalid fields
     */
    public function testUpdateFailureInvalidFields()
    {
        $this->put("/api/v1/requests/12", $this->empty_title_request);

        $this->assertResponseStatus(422);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "The title field is required.",
            $response->title[0]
        );
    }

    /**
     * Test should fail to update request that does not belong to user
     */
    public function testUpdateFailureNotOwner()
    {
        $this->makeUser("-K_nkl19N6-EGNa0WF");

        $this->put("/api/v1/requests/12", $this->requests["valid_request"]);

        $this->assertResponseStatus(403);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "You don't have permission to edit the mentorship request",
            $response->message
        );
    }

    /**
     * Test that a user can indicate interest in amentorship request
     */
    public function testUpdateInterestedSuccess()
    {
        $this->patch("/api/v1/requests/14/update-interested", [
            "interested" => ["-KXGy1MimjQgFim7u"]
        ]);

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertCount(1, $response->interested);
    }

    /*
     * Test that a user cannot indicate interest in their own request
     */
    public function testUpdateInterestedFailure()
    {
        $this->patch("/api/v1/requests/14/update-interested", [
            "interested" => ["-K_nkl19N6-EGNa0W8LF"]
        ]);

        $this->assertResponseStatus(400);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "You cannot indicate interest in your own mentorship request",
            $response->message
        );
    }

    /*
     * Test should select a mentor for  request
     */
    public function testUpdateMentorSuccess()
    {
        $this->patch("/api/v1/requests/14/update-mentor", [
            "mentor_id" => "-KXGy1MimjQgFim7u",
            "mentee_name" => "Chinazor Allen",
            "match_date" => "10"
        ]);

        $this->assertResponseOk();

        $this->assertTrue($this->google_calendar_client_mock->called);

        $request = Request::find(14);

        $this->assertEquals("-KesEogCwjq6lkOzKmLI", $request->mentor->id);
    }

    /*
     * Test that a user can cancel their own request
     */
    public function testMenteeCancelRequestSuccess()
    {
        $this->patch("/api/v1/requests/14/cancel-request?reason=mentee_cancelling");
        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Request Cancelled.", $response->message);
    }

    /*
     * Test that a user can cancel their own request
     */
    public function testAdminCancelRequestSuccess()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF", "Admin");
        $this->patch("/api/v1/requests/14/cancel-request?reason=admin_cancelling");
        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Request Cancelled.", $response->message);
    }

    /*
     * Test should prevent from cancelling someone else's request
     */
    public function testCancelRequestFailureNotOwner()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF");

        $this->patch("/api/v1/requests/14/cancel-request");

        $this->assertResponseStatus(403);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "You don't have permission to cancel this mentorship request",
            $response->message
        );
    }

    /*
     * Test that a mentee can request extension of a mentorship
     * period
     */
    public function testRequestExtensionSuccess()
    {
        $this->patch(
            "/api/v1/requests/1/update-mentor",
            [
                "mentor_id" => "-KXGy1MimjQgFim7u",
                "mentee_name" => "Chinazor Allen",
                "match_date" => "10"
            ]
        );

        $this->put("/api/v1/requests/1/extend-mentorship");

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(201);

        $this->assertEquals(
            "Your request was submitted successfully",
            $response->message
        );
    }

    /*
     * Test that a mentor can approve extension of a mentorship
     * period
     */
    public function testApproveExtensionSuccess()
    {
        $this->put("/api/v1/requests/20/extend-mentorship");

        $this->makeUser("-KesEogCwjq6lkOzKmLI");

        $this->patch("/api/v1/requests/20/approve-extension");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "Mentorship period was extended successfully",
            $response->message
        );
    }

    /*
     * Test that a mentor can reject extension of a mentorship
     * period
     */
    public function testRejectExtensionSuccess()
    {
        $this->put("/api/v1/requests/20/extend-mentorship");

        $this->makeUser("-KesEogCwjq6lkOzKmLI");

        $this->patch("/api/v1/requests/20/reject-extension");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "Mentorship extension request was rejected successfully",
            $response->message
        );
    }

    /**
     * Test approve extension failure when the logged in
     * user is not the mentor for that request
     */
    public function testApproveExtensionFailureForNotOwner()
    {
        $this->put("/api/v1/requests/20/extend-mentorship");

        $this->patch("/api/v1/requests/20/approve-extension");

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "You don't have permission to approve an extension of this mentorship",
            $response->message
        );
    }

    /**
     * Test reject extension failure when the logged in
     * user is not the mentor for that request
     */
    public function testRejectExtensionFailureForNotOwner()
    {
        $this->put("/api/v1/requests/20/extend-mentorship");

        $this->patch("/api/v1/requests/20/reject-extension");

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "You don't have permission to reject an extension of this mentorship",
            $response->message
        );
    }
}
