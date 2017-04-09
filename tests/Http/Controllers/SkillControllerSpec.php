<?php

namespace tests\App\Http\Controllers;

require __DIR__.'/../../../bootstrap/app.php';

use App\Http\Controllers\SkillController;
use Illuminate\Support\Facades\Artisan;
use Lcobucci\JWT\Parser;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use App\Skill;
use DB;

describe('Skills Controller Test', function() {
    beforeEach(function() {
        Artisan::call('migrate:refresh');
        Artisan::call('db:seed');

        $token = env('AUTH_HEADER');
        $parsed_token = (new Parser())->parse((string) $token);
        $test_auth_user = factory(\App\User::class)->create([
            'user_id' => $parsed_token->getClaim('UserInfo')->id,
            'role' => array_keys((array)$parsed_token->getClaim('UserInfo')->roles)[0],
            'firstname' => $parsed_token->getClaim('UserInfo')->first_name,
            'lastname' => $parsed_token->getClaim('UserInfo')->last_name,
            'profile_pic' => $parsed_token->getClaim('UserInfo')->picture,
        ]);

        $this->client = new Client([
            'base_uri' => env('BASE_URL'),
            'headers'  => ['Authorization'  => 'Bearer '.$token]
        ]);
    });

    describe('Search Skills Endpoint', function() {
        it('should return one skill', function() {
            $skill_query = 'php';
            $response = $this->client->get('/api/v1/skills?q='.$skill_query);
            $response_body = json_decode($response->getBody()->__toString());

            expect($response)->to->be->an->instanceof(new Response());
            expect($response->getStatusCode())->to->equal(200);
            expect(count($response_body->data))->to->equal(count(1));
            expect(strtolower($response_body->data[0]->name))->to->equal($skill_query);
        });

        it('should return all skills that match the query string', function() {
            $skill_query = 'ang';
            $matching_skills = Skill::findMatching($skill_query);
            $response = $this->client->get('/api/v1/skills?q='.$skill_query);
            $response_body = json_decode($response->getBody()->__toString());

            expect($response)->to->be->an->instanceof(new Response());
            expect($response->getStatusCode())->to->equal(200);
            expect(count($response_body->data))->to->equal(count($matching_skills));
            foreach($response_body->data as $data) {
                expect(preg_match('/ang/i', $data->name))->to->be->ok;
            }
        });

        it('should return an error message if no skill matches the query string', function() {
            try {
                $this->client->request('GET', '/api/v1/skills?q=fireservice');
            } catch (RequestException $exception) {
                $response_body = json_decode($exception->getResponse()->getBody()->__toString());

                expect($exception->getResponse()->getStatusCode())->to->equal(404);
                expect($response_body)->to->be->an('object');
                expect($response_body->message)->to->equal('skill not found');
            }
        });

        it('should return an error message if an invalid search criteria is entered', function() {
            try {
                $this->client->request('GET', '/api/v1/skills?m=fireservice');
            } catch (RequestException $exception) {
                $response_body = json_decode($exception->getResponse()->getBody()->__toString());

                expect($exception->getResponse()->getStatusCode())->to->equal(400);
                expect($response_body)->to->be->an('object');
                expect($response_body->message)->to->equal('invalid search criteria');
            }
        });

        it('should return an error message if (a) special character(s) are entered in the search term', function() {
            try {
                $this->client->request('GET', '/api/v1/skills?q=fireB*s$');
            } catch (RequestException $exception) {
                $response_body = json_decode($exception->getResponse()->getBody()->__toString());

                expect($exception->getResponse()->getStatusCode())->to->equal(400);
                expect($response_body)->to->be->an('object');
                expect($response_body->message)->to->equal('only alphanumeric characters allowed');
            }
        });
    });

    describe('Get Skill Endpoint', function() {
        it('should return all skills', function() {
            $all_skills = Skill::all();
            $response = $this->client->get('/api/v1/skills?');
            $response_body = json_decode($response->getBody()->__toString());

            expect($response)->to->be->an->instanceof(new Response());
            expect($response->getStatusCode())->to->equal(200);
            expect(count($all_skills))->to->equal(count($response_body->data));
        });

        it ('should return the skill with an id', function() {
            $matching_skill = Skill::all()->last();
            $response = $this->client->get('/api/v1/skills/'. $matching_skill->id);
            $response_body = json_decode($response->getBody()->__toString());

            expect($response)->to->be->an->instanceof(new Response());
            expect($response->getStatusCode())->to->equal(200);
            expect(count($response_body))->to->equal(count($matching_skill));
        });
    });
});
