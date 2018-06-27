<?php
/**
 * Test setup
 *
 * @return void
 */
namespace Tests\App\Http\Controllers\V2;

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
                    "roles" => ["LENKEN_ADMIN"],
                    "firstname" => "Adebayo",
                    "lastname" => "Adesanya",
                ]
            )
        );
    }

    /**
     * Creates a new user
     *
     * @param string $identifier - user's id
     * @param array  $roles      - user's roles
     */
    private function makeUser($identifier, $roles = ["Fellow"])
    {
        $this->be(
            factory(User::class)->make(
                [
                    "uid" => $identifier,
                    "name" => "Daniel Atebije",
                    "email" => "daniel.atebije@andela.com",
                    "roles" => $roles,
                    "slack_handle"=> "@danny",
                    "firstname" => "Daniel",
                    "lastname" => "Atebije",
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
        $this->get("/api/v2/notifications");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertCount(7, $response);
        $this->assertObjectHasAttribute("id", $response[0]);
        $this->assertObjectHasAttribute("default", $response[0]);
        $this->assertObjectHasAttribute("description", $response[0]);
    }
    /**
     * Test -Non Admin user can't fetch all notifications
     * 
     * @return void
     */
    public function testNonAdminCantFetchAll()
    {
        $this->makeUser("-L4g3CXhX6cPHZXTEMNE");

        $this->get("/api/v2/notifications");

        $this->assertResponseStatus(403);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("You do not have permission to perform this action.", $response->message);
    }

    
    /**
     * Test - Add new Notification Success
     *
     * @return void
     */
    public function testAddSuccess()
    {
    
        $id = "NEW_NOTIFICATION";
        $default = "in_app";
        $description = "You'll be notified when a session file is uploaded or deleted ";
        $data = array(
            "id" => $id,
            "default" => $default,
            "description" => $description
        );

        $this->post(
            "/api/v2/notifications",
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
     * Test - Non Admin can't create new notification type
     * 
     * @return void
     */

     public function testNonAdminAddFailure()
     {
         $this->makeUser("-L4g3CXhX6cPHZXTEMNE");
        $id = "NEW_NOTIFICATION";
        $default = "in_app";
        $description = "You'll be notified when a session file is uploaded or deleted ";
        $data = array(
            "id" => $id,
            "default" => $default,
            "description" => $description
        );

        $this->post(
            "/api/v2/notifications",
            $data
        );

        $this->assertResponseStatus(403);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("You do not have permission to perform this action.", $response->message);
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
            "/api/v2/notifications",
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
             "/api/v2/notifications",
             $data
         );
 
         $this->assertResponseStatus(409);
         $response = json_decode($this->response->getContent());
         $this->assertObjectHasAttribute("message", $response);
         $this->assertEquals("This notification already exists.", $response->message);
    }

    /**
     * Test - Update user notification settings success
     *
     * @return void
    */
    public function testPutSuccess()
    {
        $id = "PROFILE_SETTINGS";
        $default = "in_app";
        $description = "settings for my profile settings";
        $data = array(
            "id" => $id,
            "default" => $default,
            "description" => $description
        );

        $this->put(
            "/api/v2/notifications/INDICATES_INTEREST",
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
        $default = "in_app";
        $description = "settings for my profile settings";
        $data = array(
            "id" => $id,
            "default" => $default,
            "description" => $description
        );

        $this->put(
            "/api/v2/notifications/INDICATE_INTEREST",
            $data
        );

        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("The specified notification was not found", $response->message);
    }
    
    /**
     * Test - Delete Notification failure
     *
     * @return void
    */
    public function testDeleteFailure()
    {
        $this->delete(
            "/api/v2/notifications/4"
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
            "/api/v2/notifications/INDICATES_INTEREST"
        );

        $this->assertResponseOk();
    }

    /**
     * Test - Non Admin user can't delete notification
     * 
     * @return void
     */
    public function testNonAdminDeleteFailure()
    {
        $this->makeUser("-L4g3CXhX6cPHZXTEMNE");

        $this->delete(
            "/api/v2/notifications/INDICATES_INTEREST"
        );

        $this->assertResponseStatus(403);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("You do not have permission to perform this action.", $response->message);


    }

    /**
     * Test - Get all Notification by user_id success
     *
     * @return void
     */
    public function testGetNotifcationByUserIdSuccess()
    {
        $this->get("/api/v2/user/-KXGy1MT1oimjQgFim7u/notifications/");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response[0]);
        $this->assertObjectHasAttribute("in_app", $response[0]);
        $this->assertObjectHasAttribute("email", $response[0]);
        $this->assertObjectHasAttribute("description", $response[0]);
    }

    /**
     * Test - Get all user notification settings success
     * 
     * @return void
     */
    public function testGetAllUserSettingsSuccess()
    {
        $this->get("/api/v2/user/-KXGy1MT1oimjQgFim7u/notifications/");

        $this->assertResponseStatus(200);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response[0]);
        $this->assertObjectHasAttribute("in_app", $response[0]);
        $this->assertObjectHasAttribute("email", $response[0]);
    }

    /**
     * Test - Get all users subscribed to in_app notifications
     * for a when a request they have expressed interet in 
     * is withdrwan
     * 
     * @return void
     */
    public function testRequestInterest()
    {
        $this->get("/api/v2/notifications/request-withdrawn/28");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertContains("-K_nkl19N6-EGNa0W8LF", $response);
    }

    /**
     * Test - Get all users subscribed to in_app notifications
     * for when a skill in their profile is requested
     * 
     * @return void
     */
    public function testSkillsMatchedUsers()
    {
        $this->get("/api/v2/notifications/request-matches-skills/25");

        $this->assertResponseOk();
        $response = json_decode($this->response->getContent());
        $this->assertNotEmpty($response);
    }

    /**
     * Test - Update user settings success
     *
     * @return void
     */
    public function testUpdateUserSettingsSuccess()
    {
        $data = array(
            "user_id" => "-KXGy1MT1oimjQgFim7u",
            "id" => "WITHDRAWN_INTEREST",
            "in_app" => false,
            "email" => false
        );

        $this->put(
            "/api/v2/user/-KXGy1MT1oimjQgFim7u/notifications/WITHDRAWN_INTEREST",
            $data
        );

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("id", $response);
        $this->assertObjectHasAttribute("user_id", $response);
        $this->assertObjectHasAttribute("in_app", $response);
        $this->assertObjectHasAttribute("email", $response);
        $this->assertEquals($data["id"], $response->id);
        $this->assertEquals($data["email"], $response->email);
        $this->assertEquals($data["in_app"], $response->in_app);
    }
    
    /**
     * Test - Update user settings failure due to 
     * invalid notification id
     *
     * @return void
     */
    public function testUpdateUserSettingsFailureForInvalidId()
    {
        $data = array(
            "user_id" => "-KXGy1MT1oimjQgFim7u",
            "id" => "NO_NOTIFICATION",
            "in_app" => true,
            "email" => false
        );

        $this->put(
            "/api/v2/user/-KXGy1MT1oimjQgFim7u/notifications/NO_NOTIFICATION",
            $data
        );

        $this->assertResponseStatus(404);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("Notification does not exist.", $response->message);
    }

    /**
     * Test - Update user settings failure due to 
     * invalid user
     *
     * @return void
     */
    public function testUpdateUserSettingsFailureForInvalidUser()
    {
        $this->makeUser("-L4g3CXhX6cPHZXTEMNE");

        $data = array(
            "user_id" => "-KXGy1MT1oimjQgFim7u",
            "id" => "WITHDRAWN_INTEREST",
            "in_app" => true,
            "email" => false
        );

        $this->put(
            "/api/v2/user/-KXGy1MT1oimjQgFim7u/notifications/WITHDRAWN_INTEREST",
            $data
        );

        $this->assertResponseStatus(403);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute("message", $response);
        $this->assertEquals("You do not have permission to edit this notification settings.", $response->message);
    }
}
