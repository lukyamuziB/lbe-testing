<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\Status;
use App\Models\User;
use App\Models\Request;
use App\Models\RequestSkill;
use App\Models\RequestUsers;
use App\Models\Role;
use App\Models\RequestType;
use App\Http\Controllers\V2\RequestController;
use Test\Mocks\GoogleCalendarClientMock;

use Illuminate\Http\UploadedFile;
use TestCase;
use Carbon\Carbon;
use App\Utility\SlackUtility;

class RequestControllerTest extends TestCase
{
    private $cancellationReason = [
        "reason"=> "test cancellations reason."
    ];

    private $validAcceptOrRejectUserData = [
        "interestedUserId" => "-KesEogCwjq6lkOzKmLI",
        "interestedUserName" => "Test Admin",
    ];
    private $invalidAcceptOrRejectUserIdData = [
        "interestedUserId" => "wrongUserId",
        "interestedUserName" => "Test Admin",
    ];

    const REQUESTS_URI = "/api/v2/requests";

    private $validRequest;

    private $googleCalendarClientMock;

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

    private function makeUser($identifier, $roles = ["Fellow"])
    {
        $this->be(
            factory(User::class)->make(
                [
                    "uid" => $identifier,
                    "name" => "seyi Adeleke",
                    "email" => "adetokunbo.adeleke@andela.com",
                    "roles" => $roles,
                    "slack_handle"=> "@babyBoy",
                    "firstname" => "Adetkounbo",
                    "lastname" => "Adeleke",
                ]
            )
        );
    }

    public function setUp()
    {
        parent::setUp();

        $this->makeUser("-K_nkl19N6-EGNa0W8LF");

        $this->validRequest = $this->getValidRequest('4');

        $this->googleCalendarClientMock = new GoogleCalendarClientMock();

        $this->app->instance("App\Clients\GoogleCalendarClient", $this->googleCalendarClientMock);
    }

    /**
     * Test to get a single request.
     *
     * @return void
     */
    public function testGetRequestSuccess()
    {
        $createdRequest = $this->createRequest("-K_nkl19N6-EGNa0W8LF", "javascript", "-KesEogCwjq6lkOzKmLI", 2);
        $createdRequestId = $createdRequest->id;

        $this->get("/api/v2/requests/$createdRequestId");

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);
        $this->assertNotEmpty($response);
        $this->assertEquals($createdRequestId, $response->id);
    }

    /**
     * Test for get request failure when an invalid request id is passed.
     *
     * @return void
     */
    public function testGetRequestFailureForInvalidRequestId()
    {
        $fakeRequestId = 1223;
        $this->get("/api/v2/requests/$fakeRequestId");

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(404);
        $this->assertEquals("Request not found.", $response->message);
    }

    /**
     * Test for get all requests created by a user
     *
     * @return void
     */
    public function testGetCreatedByUserRequestSuccess()
    {
        $this->get("/api/v2/requests?category=myRequests");

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($response);
    }

    /**
     * Test to get the completed requests of a user
     *
     * @return void
     */
    public function testGetCompletedRequestsSuccess()
    {
        $this->createRequest("-K_nkl19N6-EGNa0W8LF", "javascript", "-KesEogCwjq6lkOzKmLI", Status::COMPLETED);
        $this->get("/api/v2/requests/history");

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($response);
    }

    /**
     * Test pending requests are retrieved successfully
     *
     * @return void
     */
    public function testGetPendingPoolSuccess()
    {
        $this->createRequest("-KXGy1MT1oimjQgFim7u", "javascript", "-K_nkl19N6-EGNa0W8LF", Status::OPEN);
        $this->get("/api/v2/requests/pending");

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);
        $this->assertEquals(
            "-K_nkl19N6-EGNa0W8LF",
            $response[1]->created_by->id
        );

        $this->assertContains(
            "-KesEogCwjq6lkOzKmLI",
            $response[1]->interested
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
     * Test to filter requests by seeking mentor
     *
     * @return void
     */
    public function testGetRequestsPoolForSeekingMentorSuccess()
    {
        $this->get("/api/v2/requests/pool?limit=5&page=1&type=1");

        $this->assertResponseOk();

        $openRequests = json_decode(
            $this->response->getContent()
        )->requests;

        foreach ($openRequests as $openRequest) {
            $this->assertEquals($openRequest->request_type_id, 1);
        }

        $this->assertNotEmpty($openRequests);
    }

    /**
     * Test to filter requests by seeking mentee
     *
     * @return void
     */
    public function testGetRequestsPoolForSeekingMenteeSuccess()
    {
        $this->get("/api/v2/requests/pool?limit=5&page=1&type=2");

        $this->assertResponseOk();

        $openRequests = json_decode(
            $this->response->getContent()
        )->requests;

        $this->assertNotEmpty($openRequests);

        foreach ($openRequests as $openRequest) {
            $this->assertEquals($openRequest->request_type_id, 2);
        }
    }

    /**
     * Test to filter requests by seeking mentee and seeking mentor
     *
     * @return void
     */
    public function testGetRequestsPoolForSeekingMentorMenteeSuccess()
    {
        $this->get("/api/v2/requests/pool?limit=20&page=1&type=1,2");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals($response->requests[19]->request_type_id, 1);
        $this->assertEquals($response->requests[0]->request_type_id, 2);

    }

    /**
     * Test to filter requests by seeking mentee or seeking mentor for in valid input
     *
     * @return void
     */
    public function testGetRequestsPoolForSeekingMentorMenteeInvalidInput()
    {
        $this->get("/api/v2/requests/pool?limit=20&page=1&type=john");

        $this->assertResponseOk();


        $response = json_decode($this->response->getContent());

        $this->assertEquals($response->requests, []);
    }

    /**
     * Test that a mentee can cancel their own request succesfully
     *
     * @return {void}
     */
    public function testCancelRequestSuccessByMentee()
    {
        $this->patch("/api/v2/requests/19/cancel-request", $this->cancellationReason);
        $this->assertResponseOk();
    }

    /**
     * Test that admin can cancel request succesfully
     *
     * @return {void}
     */
    public function testCancelRequestSuccessByAdmin()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF", ["LENKEN_ADMIN"]);
        $this->patch("/api/v2/requests/19/cancel-request", $this->cancellationReason);
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
        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(409);
        $this->assertEquals("Mentorship Request already cancelled.", $response->message);
    }

    /**
     * Test that a user can abandon an ongoing mentorship request succesfully
     *
     * @return {void}
     */
    public function testAbandonRequestSuccess()
    {
        $this->patch("/api/v2/requests/2/update-status",
            ["status" => Status::ABANDONED, "reason"=> "test abandon reason."]);
        $this->assertResponseOk();
    }

    /**
     * Test that a user can't abandon an ongoing request in which they
     * are not involved in
     *
     * @return {void}
     */
    public function testAbandonRequestFailureForNoPermission()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF");
        $this->patch("/api/v2/requests/2/update-status",
            ["status" => Status::ABANDONED, "reason"=> "test abandon reason."]);
        $this->assertResponseStatus(401);
        $response = json_decode($this->response->getContent());
        $this->assertEquals(
            "You don't have permission to abandon this Mentorship Request.",
            $response->message
        );
    }

    /**
     * Test user can't abandon request that doesn't exist
     *
     * @return {void}
     */
    public function testAbandonRequestFailureForInvalidRequestId()
    {
        $this->patch("/api/v2/requests/2389/update-status",
        ["status" => Status::ABANDONED, "reason"=> "test abandon reason."]);
            $response = json_decode($this->response->getContent());
            $this->assertResponseStatus(404);
        $this->assertEquals("Mentorship Request not found.", $response->message);
    }

    /**
     * Test user can't abandon a request twice.
     *
     * @return {void}
     */
    public function testAbandonRequestForAlreadyAbandonedRequest()
    {
        $this->patch("/api/v2/requests/2/update-status",
            ["status" => Status::ABANDONED, "reason"=> "test abandon reason."]);
        $this->patch("/api/v2/requests/2/update-status",
            ["status" => Status::ABANDONED, "reason"=> "test abandon reason."]);
        $this->assertResponseStatus(409);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Mentorship Request already abandoned.", $response->message);
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
            "/api/v2/requests/14/indicate-interest",
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

        $mentorshipRequests = json_decode(
            $this->response->getContent()
        )->requests;
        foreach ($mentorshipRequests as $mentorshipRequest) {
            $this->assertEquals($mentorshipRequest->status_id, 1);
        }
    }


    /**
     * Test interested user is accepted succesfully
     *
     * @return void
     */
    public function testAcceptInterestedUserSuccess()
    {
        $this->patch(
            "/api/v2/requests/19/accept-user",
            $this->validAcceptOrRejectUserData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);

        $this->assertTrue($this->googleCalendarClientMock->called);

        $this->assertEquals($response->status_id, 2);

        $this->assertNotNull($response->match_date);
    }

    /**
     * Test valid interested user id is required
     *
     * @return void
     */
    public function testAcceptInterestedUserFailureInvalidInterestedUserId()
    {
        $this->patch(
            "/api/v2/requests/17/accept-user",
            $this->invalidAcceptOrRejectUserIdData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(404);
        $this->assertEquals($response->message, "The fellow is not an interested user");
    }


    /**
     * Test only requests owner can accept interested user
     *
     * @return void
     */
    public function testAcceptInterestedUserFailureUnauthorizedUser()
    {
        $this->makeUser("-Kv6NjpXJ_suEwaCsBzq");
        $this->patch(
            "/api/v2/requests/17/accept-user",
            $this->validAcceptOrRejectUserData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(403);
        $this->assertEquals(
            $response->message,
            "You do not have permission to perform this operation"
        );
    }

    /**
     * Test interested user is rejected succesfully
     *
     * @return void
     */
    public function testRejectInterestedUserSuccess()
    {
        $this->patch(
            "/api/v2/requests/19/reject-user",
            $this->validAcceptOrRejectUserData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);
        $this->assertNull($response->interested);
    }

    /**
     * Test valid interested user id is required
     *
     * @return void
     */
    public function testRejectInterestedUserFailureInvalidinterestedUserId()
    {
        $this->patch(
            "/api/v2/requests/17/reject-user",
            $this->invalidAcceptOrRejectUserIdData
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(404);
        $this->assertEquals($response->message, "The fellow is not an interested user");
    }

    /**
     * Test only owner of request can reject interested user
     *
     * @return void
     */
    public function testRejectInterestedUserFailureUnauthorizedUser()
    {
        $this->makeUser("-Kv6NjpXJ_suEwaCsBzq");
        $this->patch(
            "/api/v2/requests/17/reject-user",
            $this->validAcceptOrRejectUserData
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
        $this->validRequest["requestType"] = 1;

        $this->post(self::REQUESTS_URI, $this->validRequest);
        $response = json_decode($this->response->getContent());
        $this->createRequestAssertion($response, $this->validRequest["requestType"]);
    }

    /**
     * Test to create a valid mentee mentorship request
     *
     * @return void
     */
    public function testCreateMenteeRequest()
    {
        $this->validRequest["requestType"] = 2;

        $this->post(self::REQUESTS_URI, $this->validRequest);
        $response = json_decode($this->response->getContent());
        $this->createRequestAssertion($response, $this->validRequest["requestType"]);
    }

    /**
     * Test to create request assertions
     *
     * @param object $response created request
     * @param int $requestType request type
     *
     * @return void
     */
    private function createRequestAssertion($response, $requestType)
    {
        $this->assertResponseStatus(201);
        $this->assertEquals($requestType, $response->request_type_id);
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
        $this->assertEquals(36, $response->id);
        $response = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey("created_by", $response);
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
        $this->createRequest("-KXGy1MT1oimjQgFim7u", "javascript", "-KesEogCwjq6lkOzKmLI", Status::OPEN, Carbon::now());
        $endDate = Carbon::now()->format("d-m-Y");

        $this->get("api/v2/requests/pool?limit=5&page=1&status=&startDate=" . $startDate . "&endDate=" . $endDate);
        $response = json_decode($this->response->getContent());
        $this->assertEquals($response->pagination->total_count, 1);
    }

    /**
     * Test to check if awaiting_user is appended to requests.
     *
     * @return void
     */
    public function testAppendAwaitedUser()
    {
        $method = new \ReflectionMethod('\App\Http\Controllers\V2\RequestController', 'appendAwaitedUser');
        $method->setAccessible(true);
        $slackMock = $this->createMock(SlackUtility::class);
        $requestController = new RequestController($slackMock);
        $request = [
            (object) [
                "interested" => [],
                "mentee" => (object)["fullname"=> "tester"],
            ]
        ];
        $userId = "-KesEogCwjq6lkOzKmLI";
        $result = [
            (object) [
                "interested" => [],
                "mentee" => (object)["fullname"=> "tester"],
                "awaited_user" => "you"
            ]
        ];
        $method->invoke($requestController, $request, $userId);
        $this->assertEquals($request, $result);
    }

    /**
     * Test to check if the date range filter is accurate.
     */
    public function testGetAllRequestsDateRangeSuccess()
    {
        $startDate = Carbon::now()->format("d-m-Y");
        $this->createRequest("-KXGy1MT1oimjQgFim7u", "javascript", "-K_nkl19N6-EGNa0W8LF", Status::OPEN, Carbon::now());
        $endDate = Carbon::now()->format("d-m-Y");

        $this->get("api/v2/requests?limit=5&page=1&status=&startDate="
          . $startDate . "&endDate=" . $endDate);
        $response = json_decode($this->response->getContent());

        $this->assertEquals($response->pagination->total_count, 1);
    }

    /**
     * Test to return skill requests
     */
    public function testGetSkillRequestsSuccess()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF", ["LENKEN_ADMIN"]);
        $this->get(
            "/api/v2/skills/1/requests"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertNotEmpty($response);
        $this->assertObjectHasAttribute("requests", $response->skill);
    }

    /**
     * Test that it only returns requests where the type
     * of the skill for the skillId passed is type primary
     */
    public function testGetSkillRequestsSuccessForPrimarySkill()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF", ["LENKEN_ADMIN"]);
        $this->get(
            "/api/v2/skills/18/requests"
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseOk();
        $this->assertNotEmpty($response->skill->requests[0]->request_skills);
        $this->assertEquals($response->skill->id, $response->skill->requests[0]->request_skills[0]->id);
        $this->assertEquals($response->skill->name, $response->skill->requests[0]->request_skills[0]->name);
        $this->assertEquals("primary", $response->skill->requests[0]->request_skills[0]->type);
    }

    /**
     * Test to return error message if there skill id is invalid
     */
    public function testGetSkillRequestsFailure()
    {
        $this->makeUser("-KXKtD8TK2dAXdUF3dPF", ["LENKEN_ADMIN"]);
        $this->get(
            "/api/v2/skills/1aaaa1/requests"
        );

        $response = json_decode($this->response->getContent());
        $errorResponse = (object) [
            "message" => "Invalid parameter."
        ];
        $this->assertEquals($response, $errorResponse);
    }

    /**
     * Test to return an empty array when there are no
     * request for the rating values passed in
     *
     */
    public function testGetRequestsPoolWithoutMatchingRatings()
    {
        $this->get("api/v2/requests/pool?ratings=5,4,3,1");
        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertEquals(0, $response->pagination->total_count);
    }

    /**
     * Test to return the list of requests of a particular
     * rating value passed in
     *
     */
    public function testGetRequestsPoolWithMatchingRatings()
    {
        $this->get("api/v2/requests/pool?ratings=2");
        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertNotFalse($response->pagination->total_count > 0);
    }

    /**
     *  Test request owner can edit an unmatched request successfully
     *
     *  @return {void}
     */
    public function testEditRequestSuccess()
    {
        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
        $this->put(
            "/api/v2/requests/01",
            $this->validRequest
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(200);
        $this->assertEquals($response->request_skills[0]->id,"4");
        $this->assertEquals($response->title, "Angular 2 and PHP");
        $this->assertEquals($response->description, "I need a mentor to help me level up in Angular 2 and PHP");
    }

    /**
     *  Test request_skills table is not updated
     *  when there are no new skills
     *
     *  @return {void}
     */
    public function testAreNewSkillsAddedSuccess()
    {
        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
        $idBeforeUpdate = RequestSkill::where('request_id',23)
                    ->pluck('id')->toArray();
        $this->put(
            "/api/v2/requests/23",
            $this->getValidRequest('18')
        );

        $idAfterUpdate = RequestSkill::where('request_id',23)
                    ->pluck('id')->toArray();
        $this->assertEquals($idBeforeUpdate, $idAfterUpdate);
        $this->assertResponseStatus(200);
    }

    /**
     * Test a user cannot edit an invalid request
     *
     * @return {void}
     */
    public function testEditRequestFailureForInvalidRequestId()
    {
        $this->put("/api/v2/requests/368", $this->validRequest);
        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Mentorship request not found.", $response->message);
    }

    /**
     * Test only requests owner can edit a request
     *
     * @return {void}
     */
    public function testEditRequestFailureForUnauthorizedUser()
    {
        $this->makeUser("-KhMnFWEbnDtO72OHte7");
        $this->put(
            "/api/v2/requests/09",
            $this->validRequest
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(403);
        $this->assertEquals(
            $response->message,
            "You do not have permission to edit this mentorship request."
        );
    }
    /**
     * Test request owner can only edit an open request
     *
     * @return {void}
     */
    public function testEditRequestFailureForMatchedRequest()
    {
        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
        $this->put(
            "/api/v2/requests/10",
            $this->validRequest
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(400);
        $this->assertEquals(
            $response->message,
            "You can only edit an open request."
        );
    }

    /**
     * Gets a valid request
     *
     * @param $primarySkill - request primary skill
     *
     * @return array
     */
    private function getValidRequest($primarySkill)
    {
    return [
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
        "primary" => [$primarySkill],
        "secondary" => [],
        "preRequisite" => [],
        "location" => "Lagos",
        "status_id" => 1,
        ];
    }
     /* Test to return requests search results
     */
    public function testSearchRequestsSuccess()
    {
        $this->get(
            "/api/v2/requests/search?q=consequatur"
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseOk();
        $this->assertResponseStatus(200);

        foreach($response->requests as $request)
        {
            if (strpos($request->title, 'consequatur') !== false) {
                $this->assertContains('consequatur', $request->title);
            }

            if (strpos($request->description, 'consequatur') !== false) {
                $this->assertContains('consequatur', $request->description);
            }
        }

        $this->assertNotEmpty($response);
        $this->assertEquals(
            $response->pagination->total_count,
            count($response->requests)
        );
    }

    /**
     * Test to return message when no search query is provided
     */
    public function testSearchRequestsFailure()
    {
        $this->get(
            "/api/v2/requests/search"
        );

        $response = json_decode($this->response->getContent());
        $errorResponse = (object) [
            "message" => "No search query was given."
        ];
        $this->assertResponseStatus(400);
        $this->assertEquals($response, $errorResponse);
    }

    /**
     * Test to return requests search for query that does not exist in
     * database
     */
    public function testSearchRequestEmptyResponse()
    {
        $this->get(
            "/api/v2/requests/search?q=aswedrtrftgyhujikolpkujyhggtd"
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseOk();
        $this->assertResponseStatus(200);
        $this->assertEmpty($response->requests);
        $this->assertEquals($response->pagination->total_count, 0);
    }
}
