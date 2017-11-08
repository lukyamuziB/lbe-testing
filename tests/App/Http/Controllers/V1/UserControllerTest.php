<?php

namespace Test\App\Http\Controllers;

use TestCase;
use Test\Mocks\AISClientMock;

/**
 * Test class for user controller
 *
 * @return null
 */
class UserControllerTest extends TestCase
{
    
    /**
     * Setup function to instantiate ais client mock
     *
     * @return null
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Test for get user details successfully
     *
     * @return void
     */
    public function testGetUserDetailsSuccess()
    {
        
        $this->get("/api/v1/users/-KXGy1MTiQgFim7");

        $this->assertResponseOk();
        
        $response = $this->response->getContent();
        
        $this->assertNotEmpty($response);
        
        $this->assertContains("id", $response);

        $this->assertContains("email", $response);

        $this->assertContains("name", $response);

        $this->assertContains("picture", $response);

        $this->assertContains("first_name", $response);

        $this->assertContains("cohort", $response);

        $this->assertContains("roles", $response);

        $this->assertContains("placement", $response);

        $this->assertContains("location", $response);
    }

    /**
     * Test for get user details with invalid ID
     *
     * @return void
     */
    public function testGetUserDetailsFailure()
    {
            $this->get("/api/v1/users/-KXGy1MTimjQgFim7uh");
            
            $response = json_decode($this->response->getContent());
            
            $this->assertEquals("user not found", $response->message);
    }

    /**
     * Test for get user details with skills gained successfully
     *
     * @return void
     */
    public function testGetUserDetailsWithSkillsGainedSuccess()
    {

        $this->get("/api/v1/users/-KXGy1MTiQgFim7?include=skills_gained");

        $this->assertResponseOk();

        $response = $this->response->getContent();

        $this->assertNotEmpty($response);

        $this->assertContains("id", $response);

        $this->assertContains("email", $response);

        $this->assertContains("name", $response);

        $this->assertContains("picture", $response);

        $this->assertContains("first_name", $response);

        $this->assertContains("cohort", $response);

        $this->assertContains("roles", $response);

        $this->assertContains("placement", $response);

        $this->assertContains("location", $response);

        $this->assertContains("skills_gained", $response);
    }
}
