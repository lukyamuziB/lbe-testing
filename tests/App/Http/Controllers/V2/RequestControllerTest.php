<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use App\Models\Request;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use TestCase;

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
    private function createRequest($menteeId, $interested, $statusId)
    {
        Request::create(
            [
                "mentee_id" => $menteeId,
                "title" => "Javascript",
                "description" => "Learn Javascript",
                "status_id" => $statusId,
                "created_at" => "2017-09-19 20:55:24",
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
            $response->awaitingResponse[10]->mentee_id
        );

        $this->assertContains(
            "-K_nkl19N6-EGNa0W8LF",
            $response->awaitingYou[0]->interested
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
        $this->call("POST", "/api/v2/sessions/19/files", [], [], $fileDetails, []);
        $this->assertResponseStatus(201);
        $this->get("/api/v2/requests/in-progress/19");
        $this->assertResponseStatus(200);
        $result = json_decode($this->response->getContent());
        $file = $result[0]->files[0];
        $this->assertEquals($file->name, "test.doc");
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
            "/api/v2/requests/17/accept-mentor",
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
            "/api/v2/requests/17/reject-mentor",
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
}
