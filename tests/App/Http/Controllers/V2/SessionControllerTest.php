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
    public function testAttachSessionFileFailureNonExistentFile()
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
    public function testDetachSessionFileFailureNonExistentFile()
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
        $this->call("POST", "/api/v2/sessions/19/files", [], [], $this->fileDetails, []);
        $this->assertResponseStatus(201);
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
    public function testLogSessionSuccess()
    {
        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
        $this->post(
            "/api/v2/requests/10/sessions",
            [
                "date" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(24)->timestamp,
                "start_time" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(22),
                "end_time" => Carbon::now()->timezone('Africa/Lagos')->subHour(24),
                "comment" => "It was a cool session"
            ]
        );

        $this->assertResponseStatus(201);

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("date", $response);
        $this->assertObjectHasAttribute("start_time", $response);
        $this->assertObjectHasAttribute("end_time", $response);
        $this->assertObjectHasAttribute("comment", $response);
    }

    /**
     * Test that a user can log a session that is already logged.
     *
     * @return void
     */
    public function testLogSessionFailureForAlreadyloggedSession()
    {
        $this->makeUser("-K_nkl19N6-EGNa0W8LF");
        $sessionDate = Carbon::now()
            ->timezone('Africa/Lagos')->subHour(24)->timestamp;
        $this->post(
            "/api/v2/requests/10/sessions",
            [
                "date" => $sessionDate,
                "start_time" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(22),
                "end_time" => Carbon::now()->timezone('Africa/Lagos')->subHour(24),
                "comment" => "It was a cool session"
            ]
        );

        $this->post(
            "/api/v2/requests/10/sessions",
            [
                "date" => $sessionDate,
                "start_time" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(22),
                "end_time" => Carbon::now()->timezone('Africa/Lagos')->subHour(24),
                "comment" => "It was a cool session"
            ]
        );

        $response = json_decode($this->response->getContent());
        $this->assertResponseStatus(409);
        $this->assertEquals("Session already logged.", $response->message);
    }

    /**
     * Test that a user can't log a session of a request he/she doesn't belong.
     *
     * @return void
     */
    public function testLogSessionFailureForSessionNotBelongsToUser()
    {
        $this->makeUser("-L-x840rIAXXGQWFHmMs");
        $this->post(
            "/api/v2/requests/10/sessions",
            [
                "date" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(24)->timestamp,
                "start_time" => Carbon::now()
                    ->timezone('Africa/Lagos')->subHour(22),
                "end_time" => Carbon::now()->timezone('Africa/Lagos')->subHour(24),
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
}
