<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get("/", function () use ($app) {
    return $app->version();
});

/**
 * Routes for  requests
 */
$app->group(["prefix" => "api/v2"], function ($app) {
    $app->get("requests/pool", "RequestController@getRequestsPool");
    $app->get("requests/history", "RequestController@getUserHistory");
});

/**
 * Routes for  skills
 */
$app->group(["prefix" => "api/v2"], function ($app) {
    $app->get("skills/request-skills", "SkillController@getSkillsWithRequests");
});
