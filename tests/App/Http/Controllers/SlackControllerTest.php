<?php

namespace Test\App\Http\Controllers;

use App\Models\User;
use Test\Mocks\SlackUtilityMock;
use Test\Mocks\SlackUsersRepositoryMock;
use TestCase;

class SlackControllerTest extends TestCase
{
    /**
     * SetUp before each test
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
                    "email" => "inumidun.amao@andela.com",
                    "role" => "Admin",
                    "firstname" => "Adebayo",
                    "lastname" => "Adesanya",
                ]
            )
        );

        $slack_user_repository_mock = new SlackUsersRepositoryMock();

        $slack_utility_mock = new SlackUtilityMock($slack_user_repository_mock);

        $this->app->instance("App\Utility\SlackUtility", $slack_utility_mock);
    }

    /**
     * Test that a user can add and update their slack id
     */
    public function testAddSlackIdSuccess()
    {
        $this->post("/api/v1/messages/slack", [
            "slack_handle" => "@amao"
        ]);

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->data);

        $this->assertEquals(
            "-KXGy1MT1oimjQgFim7u",
            $response->data->user_id
        );

        $this->assertEquals(
            "inumidun.amao@andela.com",
            $response->data->email
        );
    }

    /**
     * Test that a user cannot add another user"s slack id
     * Test that a user cannot add an invalid slack id
     */
    public function testAddSlackIdFailure()
    {
        // Adding a slack id that does not belong to user
        $this->post("/api/v1/messages/slack", [
            "slack_handle" => "@bayo"
        ]);

        $this->assertResponseStatus(401);

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->message);

        $this->assertEquals(
            "wrong slack handle",
            $response->message
        );

        // Adding a slack id that does not exist
        $this->post("/api/v1/messages/slack", [
            "slack_handle" => "@i-chat"
        ]);

        $this->assertResponseStatus(404);

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->message);

        $this->assertEquals(
            "slack handle not found",
            $response->message
        );

        // Adding a slack id that is invalid
        $this->post("/api/v1/messages/slack", [
            "slack_handle" => "11i-chat"
        ]);

        $this->assertResponseStatus(422);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "The slack handle format is invalid.",
            $response->slack_handle[0]
        );
    }

    /**
     * Test that a user can send slack message to a channel
     */
    public function testSendSlackMessageSuccess()
    {
        $this->post("/api/v1/messages/slack/send", [
            "channel" => "@amao",
            "text" => "hello channel"
        ]);

        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());

        $this->assertNotEmpty($response->message);

        $this->assertNotEmpty($response->channel);

        $this->assertEquals(1, $response->ok);

        $this->assertEquals(
            "hello channel",
            $response->message->text
        );
    }

    /**
     * Test that messages are not sent to invalid channels
     */
    public function testSendSlackMessageFailure()
    {
        $this->post("/api/v1/messages/slack/send", [
            "channel" => "tessssst-note",
            "text" => "hello channel"
        ]);

        $this->assertResponseStatus(404);

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            "The Slack channel or user tessssst-note, was not found",
            $response->message
        );
    }
}
