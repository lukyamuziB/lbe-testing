<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class SessionControllerTest extends \TestCase
{

    protected $fileDetails;
    /**
     * Test setup.
     *
     * return void
     */
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
        $this->fileDetails = ["file" => UploadedFile::fake()->create("test.doc", 1000)];
        $this->call("POST", "/api/v2/files", [], [], $this->fileDetails, []);
    }

    private function makeUser($identifier, $role = "Fellow")
    {
        $this->be(
            factory(User::class)->make(
                [
                    "uid" => $identifier,
                    "name" => "jimoh hadi",
                    "email" => "jimoh.hadi@andela.com",
                    "role" => $role,
                    "slack_handle"=> "@johadi",
                    "firstname" => "Jimoh",
                    "lastname" => "Hadi",
                ]
            )
        );
    }

    /**
     * Test that session can be created.
     *
     */
    public function testCreateSessionSuccess()
    {
        $this->post("/api/v2/requests/2/sessions", ['date' => Carbon::now()->toDateString()]);

        $this->assertResponseStatus(201);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response);
    }

    /**
     * Test that a user can attach a file to a session.
     *
     */
    public function testAttachSessionFileSuccess()
    {
        $this->patch("/api/v2/sessions/18/attach", ["fileId" => 1]);

        $this->assertResponseStatus(200);
    }

    /**
     * Test that a user cannot attach a file that does not exist.
     *
     */
    public function testAttachSessionFileFailureForNonExistentFile()
    {
        $this->patch("/api/v2/sessions/19/attach", ["fileId" => 7]);

        $this->assertResponseStatus(404);
    }

    /**
     * Test that a user can detach a file from a session.
     *
     */
    public function testDetachSessionFileSuccess()
    {
        $this->patch("/api/v2/sessions/19/attach", ["fileId" => 1]);
        $this->patch("/api/v2/sessions/19/detach", ["fileId" => 1]);

        $this->assertResponseStatus(200);
    }

    /**
     * Test that a user cannot attach a file that does not exist.
     *
     */
    public function testDetachSessionFileFailureForNonExistentFile()
    {
        $this->patch("/api/v2/sessions/19/detach", ["fileId" => 7]);

        $this->assertResponseStatus(404);
    }

    /**
     * Test that a user can add a file to a session.
     *
     */
    public function testUploadSessionFileSuccess()
    {
        $this->call(
            "POST",
            "/api/v2/sessions/19/files",
            ["name" => "any name",
                "date" => Carbon::now()->toDateString(),
            ],
            [],
            $this->fileDetails,
            []
        );

        $this->assertResponseStatus(201);
        $response = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey("file", $response);
        $this->assertArrayHasKey("session_id", $response);
    }

    /**
     * Test that a user can delete a file that belongs to a session.
     */
    public function testDeleteSessionFileSuccess()
    {
        $this->patch("/api/v2/sessions/19/attach", ["fileId" => 1]);
        $this->delete("/api/v2/sessions/19/files/1");
        $this->assertResponseStatus(200);
    }

    /**
     * Test that a user can successfully get all missed, upcoming and completed sessions for
     * a request by id.
     *
     * @return void
     */
    public function testGetSessionDatesSuccess()
    {
        $this->get("api/v2/requests/2/sessions/dates");
        $this->assertResponseStatus(200);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("date", $response[0]);
        $this->assertEquals("missed", $response[0]->status);
    }

    /**
     * Test not found exception is returned if request is unavailable.
     *
     * @return void
     */
    public function testGetSessionDatesFailureNotFound()
    {
        $this->get("api/v2/requests/50/sessions/dates");
        $this->assertResponseStatus(404);
    }

    /**
     * Test that a user can get all request sessions.
     *
     * @return void
     */
    public function testGetRequestSessionsSuccess()
    {
        $this->get("api/v2/requests/2/sessions");
        $this->assertResponseStatus(200);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("date", $response[0]);
        $this->assertObjectHasAttribute("mentee_logged", $response[0]);
        $this->assertObjectHasAttribute("mentor_logged", $response[0]);
        $this->assertEquals("missed", $response[0]->status);
    }

    /**
     * Test that a user can't get sessions of request that doesn't exist.
     *
     * @return void
     */
    public function testGetRequestSessionsFailureForRequestNotFound()
    {
        $this->get("api/v2/requests/100/sessions");
        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("Mentorship Request not found.", $response->message);
    }

    /**
     * Test that a user can log a mentorship session.
     *
     * @return void
     */
    public function testUpdateSessionSuccess()
    {
        $this->post("/api/v2/requests/2/sessions", ["date" => Carbon::now()->toDateString()]);

        $session = json_decode($this->response->getContent());
        $sessionId = $session->id;

        $this->makeUser("-K_nkl19N6-EGNa0W8LF");

        $this->patch(
            "/api/v2/requests/2/sessions/$sessionId",
            [
                "date" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(24)->timestamp,
                "start_time" => "17:00",
                "end_time" => "19:00",
                "comment" => "It was a cool session"
            ]
        );

        $this->assertResponseStatus(201);
        $response = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute("date", $response);
        $this->assertObjectHasAttribute("start_time", $response);
        $this->assertObjectHasAttribute("end_time", $response);
        $this->assertEquals(true, $response->mentee_approved);
    }

    /**
     * Test that a user can log a mentorship session.
     *
     * @return void
     */
    public function testUpdateSessionWithRatingSuccess()
    {

        $this->post("/api/v2/requests/2/sessions", ["date" => Carbon::now()->toDateString()]);

        $sessionResponse = json_decode($this->response->getContent());
        $sessionId =$sessionResponse->id;

        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
        $this->patch(
            "/api/v2/requests/2/sessions/$sessionId",
            [
                "date" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(24)->timestamp,
                "start_time" => "17:00",
                "end_time" => "19:00",
                "comment" => "It was a cool session",
                "rating_values" => (object)[
                    "teaching" => "5",
                    "availability" => "4",
                    "reliability" => "4",
                    "knowledge" => "5",
                    "usefulness" => "3"
                ],
                "rating_scale" => "5"
            ]
        );

        $this->assertResponseStatus(201);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("date", $response);
        $this->assertObjectHasAttribute("start_time", $response);
        $this->assertObjectHasAttribute("end_time", $response);
        $this->assertEquals(true, $response->mentee_approved);
    }

    /**
     * Test that a user can't log a session of a request he/she doesn't belong.
     *
     * @return void
     */
    public function testUpdateSessionFailureForSessionNotBelongsToUser()
    {
        $this->post("/api/v2/requests/2/sessions", ['date' => Carbon::now()->toDateString()]);

        $sessionResponse = json_decode($this->response->getContent());
        $sessionId =$sessionResponse->id;

        $this->makeUser("-L-x840rIAXXGQWFHmMs");
        $this->patch(
            "/api/v2/requests/10/sessions/$sessionId",
            [
                "session_id" => 1,
                "date" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(24)->timestamp,
                "start_time" => "17:00",
                "end_time" => "19:00",
                "comment" => "Not your session bro!"
            ]
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(403);
        $this->assertEquals(
            "You do not have permission to log a session for this request.",
            $response->message
        );
    }

    /**
     * Test that a mentee can confirm a session successfully
     *
     * @return void
     */
    public function testConfirmSessionSuccessForMentee()
    {
        $this->makeUser("-KesEogCwjq6lkOzKmLI");
        $this->patch(
            "/api/v2/sessions/10/confirm",
            [
                "comment" => "Confirmado, amigo!",
                "ratings" => (object)[
                    "teaching" => "5",
                    "availability" => "4",
                    "reliability" => "4",
                    "knowledge" => "5",
                    "usefulness" => "3"
                ],
                "rating_scale" => "5"
            ]
        );

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("comment", $response);
        $this->assertObjectHasAttribute("ratings", $response);
        $this->assertEquals(true, $response->mentee_approved);
        $this->assertEquals(true, $response->mentor_approved);
    }

    /**
     * Test that a mentor can confirm a session successfully
     *
     * @return void
     */
    public function testConfirmSessionSuccessForMentor()
    {
        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
        $this->patch(
            "/api/v2/sessions/20/confirm",
            [
                "comment" => "Confirmado, amigo!",
                "ratings" => (object)[
                    "teaching" => "5",
                    "availability" => "4",
                    "reliability" => "4",
                    "knowledge" => "5",
                    "usefulness" => "3"
                ],
                "rating_scale" => "5"
            ]
        );

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("comment", $response);
        $this->assertObjectHasAttribute("ratings", $response);
        $this->assertEquals(true, $response->mentee_approved);
    }

    /**
     * Test that a user cannot re-confirm a confirmed session
     *
     * @return void
     */
    public function testConfirmSessionFailureForAlreadyConfirmedSession()
    {
        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
        $this->patch(
            "/api/v2/sessions/1/confirm",
            [
                "comment" => "Another one"
            ]
        );
        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(409);
        $this->assertEquals("Session already confirmed.", $response->message);
    }

    /**
     * Test that a user can confirm a session successfully
     *
     * @return void
     */
    public function testConfirmSessionFailureForSessionNotBelongsToUser()
    {
        $this->makeUser("-Z_nkl19N6-EGNa0W8LF");
        $this->patch("/api/v2/sessions/10/confirm");
        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(401);
        $this->assertEquals("You do not have permission to confirm this session.", $response->message);
    }
}
