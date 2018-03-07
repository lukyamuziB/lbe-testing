<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use App\Models\Request;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use TestCase;
use Carbon\Carbon;

class RequestControllerTest extends TestCase
{
    private $cancellationReason = [
        "reason"=> "test cancellations reason."
    ];
    private $validAcceptOrRejectMentorData = [
        "mentorId" => "-K_nkl19N6-EGNa0W8LF",
        "mentorName" => "Test Admin",
    ];
    private $invalidAcceptOrRejectMentorIdData = [
        "mentorId" => "wrongMentorId",
        "mentorName" => "Test Admin",
    ];

    const REQUESTS_URI = "/api/v2/requests";

    private $validRequest = [
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
        "isMentor" => true,
    ];

    private $invalidResponse = [
        "title" => [
            "The title field is required."
        ],
        "description" => [
            "The description field is required."
        ],
        "duration" => [
            "The duration field is required."
        ],
        "pairing.start_time" => [
            "The pairing.start time field is required."
        ],
        "pairing.end_time" => [
            "The pairing.end time field is required."
        ],
        "pairing.timezone" => [
            "The pairing.timezone field is required."
        ]
    ];

    private function makeUser($identifier, $role = "Fellow")
    {
        $this->be(
            factory(User::class)->make(
                [
                    "uid" => $identifier,
                    "name" => "seyi Adeleke",
                    "email" => "adetokunbo.adeleke@andela.com",
                    "role" => $role,
                    "slack_handle"=> "@babyBoy",
                    "firstname" => "Adetkounbo",
                    "lastname" => "Adeleke",
                ]
            )
        );
    }

    /**
     * Creates a request with the status id provided as argument
     *
     * @param string $menteeId   - The ID of the mentee.
     * @param string $interested - The ID of the user interested
     * @param int    $statusId   - The status of the request.
     *
     * @return void
     */
    private function createRequest($menteeId, $interested, $statusId, $createdAt = "2017-09-19 20:55:24")
    {
        Request::create(
            [
                "mentee_id" => $menteeId,
                "title" => "Javascript",
                "description" => "Learn Javascript",
                "status_id" => $statusId,
                "created_at" => $createdAt,
                "match_date" => null,
                "interested" => [$interested],
                "duration" => 2,
                "pairing" => json_encode(
                    [
                        "start_time" => "01:00",
                        "end_time" => "02:00",
                        "days" => ["monday"],
                        "timezone" => "EAT"
                    ]
                ),
                "location" => "Nairobi"
            ]
        );
    }

    public function setUp()
    {
        parent::setUp();
        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
    }

    /**
     * Test to get the completed requests of a user
     *
     * @return void
     */
    public function testGetCompletedRequestsSuccess()
    {
        $this->createRequest("-K_nkl19N6-EGNa0W8LF", "-KesEogCwjq6lkOzKmLI", 3);
        $this->get("/api/v2/requests/history");

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($response);
        $this->assertEquals(1, count($response));
    }

    /**
     * Test pending requests are retrieved successfully
     *
     * @return void
     */
    public function testGetPendingPoolSuccess()
    {
        $this->createRequest("-KXGy1MT1oimjQgFim7u", "-K_nkl19N6-EGNa0W8LF", 1);
        $this->get("/api/v2/requests/pending");

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(200);

        $this->assertEquals(
            "-KXGy1MT1oimjQgFim7u",
            $response->awaiting_response[10]->mentee_id
        );

        $this->assertContains(
            "-K_nkl19N6-EGNa0W8LF",
            $response->awaiting_you[0]->interested
        );
    }

    /**
     * Test to get requests that are in progress for a user
     *
     * @return void
     */
    public function testGetRequestsInProgressSuccess()
    {
        $this->get("/api/v2/requests/in-progress");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);

        $this->assertNotEmpty($response);
    }

    /**
     * Test that a mentee can cancel there own request succesfully
     *
     * @return {void}
     */
    public function testCancelRequestSuccessByMentee()
    {
        $this->patch("/api/v2/requests/14/cancel-request", $this->cancellationReason);
        $this->assertResponseOk();
    }

    /**
     * Test that admin can cancel request succesfully
     *
     * @return {void}
     */
    public function testCancelRequestSuccessByAdmin()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF", "Admin");
        $this->patch("/api/v2/requests/14/cancel-request", $this->cancellationReason);
        $this->assertResponseOk();
    }

    /**
     * Test that user can't delete request that doesn't belong to them
     *
     * @return {void}
     */
    public function testCancelRequestFailureForNoPermission()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF");
        $this->patch("/api/v2/requests/14/cancel-request");
        $this->assertResponseStatus(401);
        $response = json_decode($this->response->getContent());
        $this->assertEquals(
            "You don't have permission to cancel this Mentorship Request.",
            $response->message
        );
    }

    /**
     * Test user can't cancel request that doesn't exist
     *
     * @return {void}
     */
    public function testCancelRequestFailureForInvalidRequestId()
    {
        $this->patch("/api/v2/requests/1499/cancel-request");
        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Mentorship Request not found.", $response->message);
    }

    /**
     * Test user can't cancel request twice.
     *
     * @return {void}
     */
    public function testCancelRequestForAlreadyCancelledRequest()
    {
        $this->patch("/api/v2/requests/14/cancel-request");
        $this->patch("/api/v2/requests/14/cancel-request");
        $this->assertResponseStatus(409);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Mentorship Request already cancelled.", $response->message);
    }

    /**
     * Test user can withdraw interest from mentorship succesfully
     *
     * @return void
     */
    public function testWithdrawInterestSuccess()
    {
        $this->makeUser("-KXGy1MimjQgFim7u");
        $this->patch(
            "/api/v1/requests/14/update-interested",
            [
                "interested" => ["-KXGy1MimjQgFim7u"]
                ]
        );
        $this->patch("api/v2/requests/14/withdraw-interest");
        $this->assertResponseOk();
    }

    /**
     * Test user can not withdraw interest from a Mentorship that doesn't exist
     * or request that they are not interested in
     *
     * @return void
     */
    public function testWithdrawInterestFailureforNonExistingRequest()
    {
        $this->patch("api/v2/requests/1499/withdraw-interest");
        $this->assertResponseStatus(404);
        $this->patch("api/v2/requests/14/withdraw-interest");
        $this->assertResponseStatus(400);
    }

    /**
     * Test that a user can get all files and sessions that belongs to a request.
     */
    public function testAllGetSessionFilesSuccess()
    {
        $fileDetails = ["file" => UploadedFile::fake()->create("test.doc", 1000)];
        $this->call(
            "POST",
            "/api/v2/sessions/19/files",
            [
                'name' => "any name",
                'date' => Carbon::now()->toDateString(),
            ],
            [],
            $fileDetails,
            []
        );
        $this->assertResponseStatus(201);
        $response = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey("file", $response);
        $this->assertArrayHasKey("session_id", $response);
    }

    public function testGetOpenRequestsOnlySuccess()
    {
        $this->get("api/v2/requests/pool?limit=5&page=1&status=1");
        $this->assertResponseStatus(200);
        $randomNumber = rand(1, 4);
        $mentorshipRequests = json_decode(
            $this->response->getContent()
        )->requests;
        $this->assertEquals($mentorshipRequests[$randomNumber]->status_id, 1);
    }

    /**
     * Test interested mentor is accepted succesfully
     *
     * @return void
     */
    public function testAcceptInterestedMentorSuccess()
    {
        $this->patch(
            "/api/v2/requests/19/accept-mentor",
            $this->validAcceptOrRejectMentorData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);

        $this->assertEquals($response->status_id, 2);
        $this->assertEquals($response->mentor_id, "-K_nkl19N6-EGNa0W8LF");
        $this->assertNotNull($response->match_date);
    }

    /**
     * Test valid interested mentor id is required
     *
     * @return void
     */
    public function testAcceptInterestedMentorFailureInvalidMentorId()
    {
        $this->patch(
            "/api/v2/requests/17/accept-mentor",
            $this->invalidAcceptOrRejectMentorIdData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(404);
        $this->assertEquals($response->message, "The fellow is not an interested mentor");
    }


    /**
     * Test only requests owner can accept interested mentor
     *
     * @return void
     */
    public function testAcceptInterestedmentorFailureUnauthorizedUser()
    {
        $this->makeUser("-Kv6NjpXJ_suEwaCsBzq");
        $this->patch(
            "/api/v2/requests/17/accept-mentor",
            $this->validAcceptOrRejectMentorData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(403);
        $this->assertEquals(
            $response->message,
            "You do not have permission to perform this operation"
        );
    }

    /**
     * Test interested mentor is rejected succesfully
     *
     * @return void
     */
    public function testRejectInterestedMentorSuccess()
    {
        $this->patch(
            "/api/v2/requests/19/reject-mentor",
            $this->validAcceptOrRejectMentorData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);
        $this->assertNull($response->interested);
    }

    /**
     * Test valid interested mentor id is required
     *
     * @return void
     */
    public function testRejectInterestedMentorFailureInvalidMentorId()
    {
        $this->patch(
            "/api/v2/requests/17/reject-mentor",
            $this->invalidAcceptOrRejectMentorIdData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(404);
        $this->assertEquals($response->message, "The fellow is not an interested mentor");
    }

    /**
     * Test only owner of request can reject interested mentor
     *
     * @return void
     */
    public function testRejectInterestedMentorFailureUnauthorizedUser()
    {
        $this->makeUser("-Kv6NjpXJ_suEwaCsBzq");
        $this->patch(
            "/api/v2/requests/17/reject-mentor",
            $this->validAcceptOrRejectMentorData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(403);
        $this->assertEquals(
            $response->message,
            "You do not have permission to perform this operation"
        );
    }

    /**
     * Test to create a valid mentor mentorship request
     *
     * @return void
     */
    public function testCreateMentorRequestSuccess()
    {
        unset($this->validRequest["isMentor"]);
        $this->post(self::REQUESTS_URI, $this->validRequest);
        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(201);
        $this->assertEquals($this->validRequest["title"], $response->title);
        $this->assertEquals($this->validRequest["description"], $response->description);
        $this->assertEquals($this->validRequest["duration"], $response->duration);
        $this->assertEquals(
            $this->validRequest["pairing"]["start_time"],
            $response->pairing->start_time
        );
        $this->assertEquals(
            $this->validRequest["pairing"]["end_time"],
            $response->pairing->end_time
        );
        $this->assertEquals(
            $this->validRequest["pairing"]["days"][0],
            $response->pairing->days[0]
        );
        $this->assertEquals($this->validRequest["location"], $response->location);
        $this->assertEquals($this->validRequest["status_id"], $response->status_id);
        $this->assertEquals(1, $response->status_id);
        $this->assertEquals(21, $response->id);
        $response = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey("mentee_id", $response);
    }

    /**
     * Test should fail if the user does not provide the necessary fields
     * required to create a mentorship request
     *
     * @return void
     */
    public function testCreateRequestFailureForMissingRequiredFields()
    {
        $partialRequest = ["title", "description", "duration"];
        $partialRequest = array_fill_keys($partialRequest, "");
        $pairing = ["start_time" => "", "end_time" => ""];
        $invalidRequest = array_merge($partialRequest, $pairing);
        $this->post(self::REQUESTS_URI, $invalidRequest);
        $this->assertResponseStatus(422);
        $response = json_decode($this->response->getContent());
        $this->assertEquals($this->invalidResponse["title"][0], $response->title[0]);
        $this->assertEquals($this->invalidResponse["description"][0], $response->description[0]);
        $this->assertEquals($this->invalidResponse["duration"][0], $response->duration[0]);
        $response = (array)$response;
        $this->assertEquals(
            $this->invalidResponse["pairing.start_time"][0],
            $response["pairing.start_time"][0]
        );
        $this->assertEquals(
            $this->invalidResponse["pairing.end_time"][0],
            $response["pairing.end_time"][0]
        );
        $this->assertEquals(
            $this->invalidResponse["pairing.timezone"][0],
            $response["pairing.timezone"][0]
        );
    }
    /**
     * Test should fail when the user tries to indicate interest in his/her own request
     *
     * @return void
     */
    public function testUpdateInterestFailureForIndicatingInOwnRequest()
    {
        $this->patch('/api/v2/requests/15/indicate-interest');
        $this->assertResponseStatus(400);
        $response = json_decode($this->response->getContent());
        $this->assertEquals(
            "You can't indicate interest in your own request.",
            $response->message
        );
    }

    /**
     * Test should fail when the user tries to indicate interest in non-existing request
     *
     * @return void
     */
    public function testUpdateInterestFailureForNonExistingRequest()
    {
        $this->patch('/api/v2/requests/500/indicate-interest');
        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "Mentorship Request not found.",
            $response->message
        );
    }

    /**
     * Test should fail when the user tries to indicate interest in a request
     * he/she already indicated interest
     *
     * @return void
     */
    public function testUpdateInterestFailureForAlreadyIndicatedInterest()
    {
        $this->makeUser("-KesEogCwjq6lkOzKmLI");
        $this->patch('/api/v2/requests/15/indicate-interest');
        $this->patch('/api/v2/requests/15/indicate-interest');
        $this->assertResponseStatus(409);
        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "You have already indicated interest in this request.",
            $response->message
        );
    }

    /**
     * Test the user can indicate interest in request if all requirements are met
     *
     * @return void
     */
    public function testUpdateInterestSuccess()
    {
        $this->makeUser("-KesEogCwjq6lkOzKmLI");
        $this->patch('/api/v2/requests/15/indicate-interest');
        $this->assertResponseStatus(200);
    }

    /**
     * Test to check if the date range filter is accurate.
     */
    public function testGetRequestDateRangePoolSuccess()
    {
        $startDate = Carbon::now()->format("d-m-Y");
        $this->createRequest("-KXGy1MT1oimjQgFim7u", "-KesEogCwjq6lkOzKmLI", 1, Carbon::now());
        $endDate = Carbon::now()->format("d-m-Y");

        $this->get("api/v2/requests/pool?limit=5&page=1&status=&startDate=" . $startDate . "&endDate=" . $endDate);
        $response = json_decode($this->response->getContent());
        $this->assertEquals($response->pagination->total_count, 1);
    }

}
