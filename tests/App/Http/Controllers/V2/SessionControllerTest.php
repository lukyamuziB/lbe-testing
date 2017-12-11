<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use Illuminate\Http\UploadedFile;

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
}
