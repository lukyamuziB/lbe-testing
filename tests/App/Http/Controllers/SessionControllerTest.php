<?php

namespace Test\App\Http\Controllers;

use App\Models\User;
use TestCase;
use Carbon\Carbon;

class SessionControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->be(
            factory(User::class)->make(
                [
                    "uid" => "-KXGy1MT1oimjQgFim7u",
                    "name" => "Adebayo Adesanya",
                    "email" => "inumidun.amao@andela.com",
                    "role" => "Admin",
                    "firstname" => "Adebayo",
                    "lastname" => "Adesanya",
                ]
            )
        );
    }

    /*
     * Test to get all session logged for a particular request
     */
    public function testGetSessionsReportSuccess()
    {
        $this->get("/api/v1/sessions/1");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertCount(1, $response->data->sessions);

        $this->assertEquals(
            1,
            $response->data->sessions[0]->rating_count
        );

        $this->assertTrue($response->data->sessions[0]->mentee_approved);
    }

    /**
     * Test get total logged session
     */
    public function testGetSessionsReportSuccessSessionLogged()
    {
        $this->get("/api/v1/sessions/2?include=totalSessionsLogged");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertEquals(0, $response->data->totalSessionsLogged);
    }

    /**
     * Test get total sessions pending
     */
    public function testGetSessionsReportSuccessSessionsPending()
    {
        $this->get("/api/v1/sessions/2?include=totalSessionsPending");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertEquals(3, $response->data->totalSessionsPending);
    }

    /**
     * Test get total sessions unlogged
     */
    public function testGetSessionsReportSuccessSessionsUnlogged()
    {
        $this->get("/api/v1/sessions/2?include=totalSessionsUnlogged");

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertGreaterThanOrEqual(0, $response->data->totalSessionsUnlogged);
    }

    /*
     * Test that a user cannot get a session whose request does not exist
     */
    public function testGetSessionsReportFailure()
    {
        $this->get("/api/v1/sessions/35");

        $this->assertResponseStatus(404);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "The specified mentor request was not found",
            $response->message
        );
    }

    /*
     * Test that a user can log a session
     */
    public function testLogSessionSuccess()
    {
        $this->get('/api/v1/requests/3');

        $existing_request = json_decode($this->response->getContent());

        // Mentee should log a session
        $this->post("/api/v1/sessions", [
            "request_id" => 3,
            "user_id" => $existing_request->data->mentee_id,
            "date" => Carbon::now()->subHour(24)->timestamp,
            "start_time" => Carbon::now()->subHour(22),
            "end_time" => Carbon::now()->subHour(24)
        ]);
        
        $this->assertResponseStatus(201);

        $response = json_decode($this->response->getContent());
         
        $this->assertTrue($response->data->mentee_approved);

        // Mentor should approve a logged session

        $session_id = $response->data->id;

        $this->patch("/api/v1/sessions/{$session_id}/approve", [
            "user_id" => $existing_request->data->mentor_id,
        ]);

        $this->assertResponseStatus(200);

        $response = json_decode($this->response->getContent());

        $this->assertTrue($response->data->mentor_approved);
    }


    /**
     * Test that a user can reject a logged session
     *
     * @return Object - response containing session data
     */
    public function testRejectSessionSuccessForMenteeReject()
    {
        // Mentee may reject a logged session
        $this->patch(
            "/api/v1/sessions/4/reject",
            [
            "user_id" => "-K_nkl19N6-EGNa0W8LF",
            ]
        );

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(200);

        $this->assertTrue($response->mentee_approved === false);
        
        $this->assertNotEmpty($response->mentee_logged_at);
    }

    /**
     * Test that a user can reject a logged session
     *
     * @return Object - response containing session data
     */
    public function testRejectSessionSuccessForMentorReject()
    {
         // Mentor may reject a logged session
         $this->patch(
             "/api/v1/sessions/20/reject",
             [
             "user_id" => "-KesEogCwjq6lkOzKmLI",
             ]
         );
 
         $response = json_decode($this->response->getContent());
 
         $this->assertResponseStatus(200);
 
         $this->assertTrue($response->mentor_approved === false);
         
         $this->assertNotEmpty($response->mentor_logged_at);
    }

    /*
     * Test that a user cannot log a session that has already been logged
     */
    public function testLogSessionFailureAlreadyLogged()
    {
        $this->post("/api/v1/sessions", [
            "request_id" => 19,
            "user_id" => "-K_nkl19N6-EGNa0W8LF",
            "date" => Carbon::now()->timestamp,
            "start_time" => Carbon::now()->addHour(10),
            "end_time" => Carbon::now()->addHour(12)
        ]);

        $this->assertResponseStatus(409);

        $response = json_decode($this->response->getContent());

        $this->assertEquals("Session already logged", $response->message);
    }

    /**
     * test that user should not log a session on a request they are not a party of
     */
    public function testApproveSessionFailureUnauthorizedLog()
    {
        $this->post("/api/v1/sessions", [
            "request_id" => 20,
            "user_id" => "-Xjsjs87d9djdsjdijd7u",
            "date" => Carbon::now()->subHour(24)->timestamp,
            "start_time" => Carbon::now()->subHour(22),
            "end_time" => Carbon::now()->subHour(24)
        ]);

        $this->assertResponseStatus(403);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "You do not have permission to log a session for this request",
            $response->message
        );
    }

    /**
     * Test that user cannot approve a session on a request they are not a party of
     */
    public function testApproveSessionFailureUnauthorizedApprove()
    {
        $this->patch("/api/v1/sessions/2/approve", [
            "user_id" => 'ussjssjsjjdjdjdjdj',
        ]);

        $this->assertResponseStatus(403);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "You do not have permission to approve this session",
            $response->message
        );
    }

    /**
     * Test that user cannot reject a session on a request they are not a party of
     *
     * @return Object - response containing the error message
     */
    public function testRejectSessionFailureUnauthorizedReject()
    {
         $this->patch(
             "/api/v1/sessions/2/reject",
             [
             "user_id" => 'ussjssjsjjdjdjdjdj',
             ]
         );

         $this->assertResponseStatus(403);

         $response = json_decode($this->response->getContent());

         $this->assertEquals(
             "You do not have permission to reject this session",
             $response->message
         );
    }

    /**
     * Test that a user cannot reject a session that does not exist
     *
     * @return Object - response containing the error message
     */
    public function testRejectSessionFailureNonExistentSession()
    {
        $this->patch(
            "/api/v1/sessions/80/reject",
            [
            "user_id" => "-KesEogCwjq6lkOzKmLI"
            ]
        );
        $this->assertResponseStatus(404);
 
        $response = json_decode($this->response->getContent());
 
        $this->assertEquals(
            "Session does not exist",
            $response->message
        );
    }
}
