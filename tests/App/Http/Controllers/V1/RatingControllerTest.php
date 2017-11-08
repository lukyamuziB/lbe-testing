<?php

namespace Test\App\Http\Controllers;

use App\Models\User;
use TestCase;

class RatingsControllerTest extends TestCase
{
    private $rating = [
        "session_id" => 10,
        "scale" => 5,
        "values" => [
            "availability" => 3,
            "reliability" => 4,
            "knowledge" => 3,
            "teaching" => 1,
            "usefulness" => 5
        ]
    ];

    private $user = [
        "name" => "Adebayo Adesanya",
        "email" => "adebayo.adesanya@andela.com",
        "role" => "Admin",
        "slack_id" => "C63LPE124",
        "firstname" => "Adebayo",
        "lastname" => "Adesanya"
    ];

    /**
     * Setup test dependencies
     */
    public function setUp()
    {
        parent::setUp();

        $this->be(
            factory(User::class)->make(
                array_merge($this->user, [ "uid" => "-K_nkl19N6-EGNa0W8LF"])
            )
        );
    }

    /**
     * Test for rate session success
     *
     * @return null
     */
    public function testRateSessionSuccess()
    {
        $this->post("/api/v1/ratings", $this->rating);

        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(201);

        $this->assertEquals(
            $response->message,
            "You have rated your session successfully"
        );
    }

    /**
     * Test for rate session failures
     *
     * @return null
     */
    public function testRateSessionsFailure()
    {
        // Incorrect Session id
        $this->post(
            "/api/v1/ratings",
            [
                "session_id" => '',
                "scale" => 5,
                "values" => [
                    "availability" => 3,
                    "reliability" => 4,
                    "knowledge" => 4,
                    "teaching" => 4,
                    "usefulness" => 5
                ]
            ]
        );
        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(422);
        $this->assertEquals(
            $response->session_id[0],
            'The session id field is required.'
        );

        // Invalid User
        $this->be(
            factory(User::class)->make(
                array_merge($this->user, [ "uid" => "-KesEogCwjq6lkOzKmLI" ])
            )
        );

        $this->post("/api/v1/ratings", $this->rating);
        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(403);
        $this->assertEquals(
            $response->message,
            'You are not allowed to rate this session'
        );


        // Invalid Session
        $this->post(
            "/api/v1/ratings",
            [
                "session_id" => 0,
                "scale" => 5,
                "values" => [
                    "availability" => '',
                    "reliability" => '',
                    "knowledge" => '',
                    "teaching" => '',
                    "usefulness" => ''
                ]
            ]
        );
        $response = json_decode($this->response->getContent());

        $this->assertResponseStatus(404);
        $this->assertEquals(
            $response->message,
            'Session does not exist'
        );
    }
}
