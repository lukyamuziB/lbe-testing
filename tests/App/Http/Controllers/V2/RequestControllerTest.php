<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use App\Models\Request;

use TestCase;

class RequestControllerTest extends TestCase
{
    private $cancellationReason = [
        "reason"=> "test cancellations reason."
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
     * @return void
     */
    private function makeCompletedRequest()
    {
        Request::create(
            [
                "mentee_id" => "-K_nkl19N6-EGNa0W8LF",
                "title" => "Javascript",
                "description" => "Learn Javascript",
                "status_id" => 3,
                "created_at" => "2017-09-19 20:55:24",
                "match_date" => null,
                "interested" => ["-K_nkl19N6-EGNa0W8LF"],
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
        $this->makeCompletedRequest();
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
        $this->get("/api/v2/requests/pending");

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);

        $this->assertEquals(
            "-K_nkl19N6-EGNa0W8LF",
            $response->requestsWithInterests[0]->mentee_id
        );

        $this->assertContains(
            "-K_nkl19N6-EGNa0W8LF",
            $response->requestsInterestedIn[0]->interested
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
}
