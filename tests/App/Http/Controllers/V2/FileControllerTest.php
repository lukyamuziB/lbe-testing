<?php

namespace Test\App\Http\Controllers\V2;

use App\Models\User;
use Illuminate\Http\UploadedFile;

class FileControllerTest extends \TestCase
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
     * Test that a user can download a file successfully.
     *
     */
    public function testDownloadFileSuccess()
    {
        $this->get("/api/v2/files/1");
        $this->assertResponseStatus(200);
    }

    /**
     * Test that a user cannot download a file that does not exist.
     *
     */
    public function testDownloadFileFailureNoFile()
    {
        $this->get("/api/v2/files/7");
        $this->assertResponseStatus(404);
    }

    /**
     * Test that a user can upload a file successfully.
     *
     */
    public function testUploadFileSuccess()
    {
        $this->call("POST", "/api/v2/files", [], [], $this->fileDetails, []);
        $this->assertResponseStatus(201);
    }

    /**
     * Test that a user cannot upload a file that does not exist.
     *
     */
    public function testUploadFileFailureNoFile()
    {
        $this->post("/api/v2/files", [
            "source" => "public/"
        ]);
        $this->assertResponseStatus(400);
    }

    /**
     * Test that an admin can delete a file.
     *
     */
    public function testDeleteFileSuccess()
    {
        $this->delete("/api/v2/files/1");
        $this->assertResponseStatus(200);
    }

    /**
     * Test that a user cannot delete a file that does not exist.
     *
     */
    public function testDeleteFileFailureNoFile()
    {
        $this->delete("/api/v2/files/3");
        $this->assertResponseStatus(404);
    }
}
