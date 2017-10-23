<?php
/**
 * Test setup
 *
 * @return void
 */
namespace Tests\App\Http\Controllers;

use App\Models\User;
use TestCase;

class NotificationControllerTest extends TestCase
{   
    /**
     * Test setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->be(
            factory(User::class)->make(
                [
                    "uid" => "-KXGy1MT1oimjQgFim7u",
                    "name" => "Adebayo Adesanya",
                    "email" => "adebayo.adesanya@andela.com",
                    "role" => "Admin",
                    "firstname" => "Adebayo",
                    "lastname" => "Adesanya",
                ]
            )
        );
    }

    /**
     * Test - Fetch all Notification Success
     *
     * @return void
     */
    public function testAllSuccess()
    {
        $this->get("/api/v1/notifications");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertCount(5, $response);
        $this->assertObjectHasAttribute("id", $response[0]);
        $this->assertObjectHasAttribute("default", $response[0]);
        $this->assertObjectHasAttribute("description", $response[0]);
    }
    /**
     * Test - Add new Notification Success
     *
     * @return void
     */
    public function testAddSuccess()
    {   
        $id = "PROFILE_SETTINGS";
        $default = "slack";
        $description = "settings for my profile settings";
        $data = array(
            "id" => $id,
            "default" => $default,
            "description" => $description
        );

        $this->post(
            "/api/v1/notifications",
            $data
        );

        $this->assertResponseStatus(201);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response);
        $this->assertObjectHasAttribute("description", $response);
        $this->assertEquals($id, $response->id);
        $this->assertEquals($description, $response->description);
    }
    /**
     * Test - Add new Notification failure
     *
     * @return void
     */
    public function testAddFailureForInvalidInput()
    {   
        $id = "my profile settings";
        $description = "settings for my profile settings";
        $data = array(
            "id" => $id,
            "default" => "",
            "description" => $description
        );

        $this->post(
            "/api/v1/notifications",
            $data
        );

        $this->assertResponseStatus(422);
        $response = json_decode($this->response->getContent());
        $this->assertEquals("The id format is invalid.", $response->id[0]);
        $this->assertEquals("The default field is required.", $response->default[0]);
    }

    /**
     * Test - Add new Notification failure
     *
     * @return void
     */
     public function testAddFailureForDuplicates()
     {   
         $id = "INDICATES_INTEREST";
         $description = "settings for my profile settings";
         $data = array(
             "id" => $id,
             "default" => "email",
             "description" => $description
         );
 
         $this->post(
             "/api/v1/notifications",
             $data
         );
 
         $this->assertResponseStatus(409);
         $response = json_decode($this->response->getContent());
         $this->assertObjectHasAttribute("message", $response);
         $this->assertEquals("Notification already exists", $response->message);
     }

    /**
     * Test - Update user notification settings success
     *
     * @return void
     */
    public function testPutSuccess()
    {
        $id = "PROFILE_SETTINGS";
        $default = "slack"; 
        $description = "settings for my profile settings";
        $data = array(
            "id" => $id,
            "default" => $default,
            "description" => $description
        );

        $this->put(
            "/api/v1/notifications/INDICATES_INTEREST",
            $data
        );

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response);
        $this->assertObjectHasAttribute("default", $response);
        $this->assertEquals($id, $response->id);
        $this->assertEquals($default, $response->default);
        $this->assertEquals($description, $response->description);
    }
    /**
     * Test - Update user notification settings failure
     *
     * @return void
     */
    public function testPutFailureForInvalidId()
    {
        $id = "PROFILE_SETTINGS";
        $default = "slack";
        $description = "settings for my profile settings";
        $data = array(
            "id" => $id,
            "default" => $default,
            "description" => $description
        );

        $this->put(
            "/api/v1/notifications/INDICATE_INTEREST",
            $data
        );

        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("The specified notification was not found", $response->message);
    }
    
    public function testPutFailureForInvalidInput()
    {
        $id = "PROFILE_SETTINGS";
        $default = "";
        $description = "settings for my profile settings";
        $data = array(
            "id" => $id,
            "default" => $default,
            "description" => $description
        );

        $this->put(
            "/api/v1/notifications/INDICATE_INTEREST",
            $data
        );

        $this->assertResponseStatus(422);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("default", $response);
        $this->assertEquals("The default field is required.", $response->default[0]);
    }

     /**
      * Test - Delete Notification failure
      *
      * @return void
      */
    public function testDeleteFailure()
    {
        $this->delete(
            "/api/v1/notifications/4"
        );

        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("The specified notification was not found", $response->message);
    }

     /**
      * Test - Delete Notification success
      *
      * @return void
      */
    public function testDeleteSuccess()
    {
        $this->delete(
            "/api/v1/notifications/INDICATES_INTEREST"
        );

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response);
        $this->assertObjectHasAttribute("description", $response);
        $this->assertEquals("INDICATES_INTEREST", $response->id);
    }

    /**
     * Test - Get all Notification by user_id success
     *
     * @return void
     */
    public function testGetNotifcationByUserIdSuccess()
    {
        $this->get("/api/v1/user/-KXGy1MT1oimjQgFim7u/settings");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response[0]);
        $this->assertObjectHasAttribute("slack", $response[0]);
        $this->assertObjectHasAttribute("email", $response[0]);
        $this->assertObjectHasAttribute("description", $response[0]);

    }

    /**
     * Test - Update user settings success
     *
     * @return void
     */
    public function testUpdateUserSettingsSuccess()
    {
        $data = array(
            "user_id" => "-K_nkl19N6-EGNa0W8LF",
            "id" => "INDICATES_INTEREST",
            "slack" => true,
            "email" => true
        );

        $this->put(
            "/api/v1/user/-KXGy1MT1oimjQgFim7u/settings/INDICATES_INTEREST",
            $data
        );

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response);
        $this->assertObjectHasAttribute("user_id", $response);
        $this->assertObjectHasAttribute("slack", $response);
        $this->assertObjectHasAttribute("email", $response);
        $this->assertEquals($data["id"], $response->id);
        $this->assertEquals($data["email"], $response->email);
        $this->assertEquals($data["slack"], $response->slack);
    }
    
    /**
     * Test - Update user settings failure
     *
     * @return void
     */
    public function testUpdateUserSettingsFailureForInvalidId()
    {
        $data = array(
            "user_id" => "-K_nkl19N6-EGNa0W8LF",
            "id" => "INDICATES_INTEREST",
            "slack" => true,
            "email" => true
        );

        $this->put(
            "/api/v1/user/-KXGy1MT1oimjQgFim7u/settings/INDICATE_INTEREST",
            $data
        );

        $this->assertResponseStatus(400);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("Notification does not exist", $response->message);
    }

    /**
     * Test - Update user settings failure
     *
     * @return void
     */
     public function testUpdateSettingsFailureForInvalidInput()
     {
         $data = array(
             "user_id" => "",
             "id" => "",
         );
 
         $this->put(
             "/api/v1/user/-KXGy1MT1oimjQgFim7u/settings/INDICATE_INTEREST",
             $data
         );
 
         $this->assertResponseStatus(422);
         $response = json_decode($this->response->getContent());
         $this->assertObjectHasAttribute("user_id", $response);
         $this->assertObjectHasAttribute("slack", $response);
         $this->assertObjectHasAttribute("email", $response);
         $this->assertObjectHasAttribute("id", $response);
         $this->assertEquals("The email field is required.", $response->email[0]);
         $this->assertEquals("The user id field is required.", $response->user_id[0]);
         $this->assertEquals("The slack field is required.", $response->slack[0]);
         $this->assertEquals("The id field is required.", $response->id[0]);
     }
}
