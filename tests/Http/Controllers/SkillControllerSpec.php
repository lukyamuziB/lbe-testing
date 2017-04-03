<?php

namespace tests\App\Http\Controllers;

use App\Http\Controllers\SkillController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpSpec\ObjectBehavior;
use GuzzleHttp\Client;
use Prophecy\Argument;
use App\Skill;


class SkillControllerSpec extends ObjectBehavior
{

    const BASE_URL    = 'http://127.0.0.1:3000/api/v1';
    const AUTH_HEADER = 'Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJVc2VySW5mbyI6eyJpZCI6Ii1LWXNTWC1laW1LeWtYVFp0aUhFIiwiZW1haWwiOiJqb3NlcGguYWtoZW5kYUBhbmRlbGEuY29tIiwiZmlyc3RfbmFtZSI6Ikpvc2VwaCIsImxhc3RfbmFtZSI6IkFraGVuZGEiLCJuYW1lIjoiSm9zZXBoIEFraGVuZGEiLCJwaWN0dXJlIjoiaHR0cHM6Ly9saDQuZ29vZ2xldXNlcmNvbnRlbnQuY29tLy1tanJ1OUxzRjY4WS9BQUFBQUFBQUFBSS9BQUFBQUFBQUFBby8telhXelVIVmp1WS9waG90by5qcGc_c3o9NTAiLCJyb2xlcyI6eyJGZWxsb3ciOiItS1hHeTFFQjFvaW1qUWdGaW02QyJ9LCJwZXJtaXNzaW9ucyI6eyJFRElUX01ZX1BST0ZJTEUiOiItS1hGNUJ1UElWa29KenRZY2N3ZiIsIlRSQUNLX1ZPRiI6Ii1LZE40S1ROakZ3SmdCMWx3akxOIiwiVklFV19FTkdBR0VNRU5UIjoiLUtmRTU3QzFpalhKMmhmc2lZQkciLCJWSUVXX1BST1NQRUNUUyI6Ii1LZkNES3EwWDdNVF9Ua2s0U3Z5IiwiVklFV19SQVRJTkdTIjoiLUtYQnp0ZnRjZU12YnY5ZUpBQzAiLCJWX0ZFTExPV19MT0NBVElPTiI6Ii1LWEhkbEpJU3NYU0V5b2U2V2pSIiwiVl9PUFBfRU5HX1RZUEUiOiItS1hIZHA4Ymg2c2JLOWJ1eE9qaCJ9fSwiZXhwIjoxNDg5ODU5NjY2fQ.I1I6frCC-FCbJbapFBtdJMWDllMOb1sXxOjO1fUU00Vsspg_yDiD1Wa3OUrI8btibYw5NPLRpOj9yaHXlXVEhZMot4XDZ38d7FAmuFFMoE67WfJLLbq7Ee3HVdlkBFzHGKK0iHKbdwqR_0LBfL3ehl7FNApJ7Ho4NMLujloljBU';

    function let()
    {
        $client = new Client([
            'base_uri' => 'http://localhost:2000',
            'headers'  => ['Authorization'  => self::AUTH_HEADER]
        ]);
        $this->beConstructedWith($client);
    }

    function it_is_initializable()
    {
      $this->shouldHaveType(SkillController::class);
    }

    /**
     * Test for a successful call to GET all skills
     */
    function it_should_return_all_skills()
    {
        $result = $this->shadowModel();
        dd($result);

        $res = $this->client('GET', '/api/v1/requests');
	    $res->getStatusCode()->shouldBe(200);
	    $this->parseJSONToArray($res->getBody())->shouldHaveCount(count($m));
    }

    /**
     * Test successful call to GET a particluar skill
     */
    // function it_should_retun_one_skill(Request $request)
    // {
    //     $client = new Client([
    //         'base_uri' => 'http://localhost:2000',
    //         'exceptions' => false
    //     ]);
    //
    //     $client->post('/skills', ['name' => 'php']);
    //     $response = $client->get('api/v1/skills?name=php');
    //     $this->all($request)->content()->shouldContain('php');
    // }
}
