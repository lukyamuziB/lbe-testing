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
    $app->get("requests/in-progress", "RequestController@getRequestsInProgress");
    $app->get("requests/pending", "RequestController@getPendingPool");
    $app->patch("requests/{id}/cancel-request", "RequestController@cancelRequest");
    $app->patch("requests/{id}/withdraw-interest", "RequestController@withdrawInterest");
    $app->get("requests/in-progress/{id}", "SessionController@getAllSessions");
    $app->patch("requests/{id}/accept-mentor", "RequestController@acceptInterestedMentor");
    $app->patch("requests/{id}/reject-mentor", "RequestController@rejectInterestedMentor");
    $app->get("requests/status-statistics", "ReportController@getRequestsStatusStatistics");
    $app->post("requests", "RequestController@createRequest");
});
/**
 * Routes for skills
 */
$app->group(["prefix" => "api/v2"], function ($app) {
    $app->get("skills/request-skills", "SkillController@getSkillsWithRequests");
    $app->post("users/{userId}/skills", "SkillController@addUserSkill");
    $app->delete("users/{userId}/skills/{skillId}", "SkillController@deleteUserSkill");
});
/**
 * Routes for users
 */
$app->group(["prefix" => "api/v2"], function ($app) {
    $app->get("users", "UserController@getUsersByIds");
    $app->get("users/{id}", "UserController@get");
    $app->post("users/{userId}/skills", "UserController@addUserSkill");
    $app->delete("users/{userId}/skills/{skillId}", "UserController@deleteUserSkill");
});

/**
 * Routes for sessions
 */
$app->group(["prefix" => "api/v2"], function ($app) {
    $app->post("sessions/{id}/files", "SessionController@uploadSessionFile");
    $app->delete("sessions/{id}/files/{fileId}", "SessionController@deleteSessionFile");
    $app->patch("sessions/{id}/attach", "SessionController@attachSessionFile");
    $app->patch("sessions/{id}/detach", "SessionController@detachSessionFile");
});

/**
 * Routes for files manipulation
 */
$app->group(["prefix" => "api/v2"], function ($app) {
    $app->get("files/{id}", "FilesController@downloadFile");
    $app->post("files", "FilesController@uploadFile");
    $app->delete("files/{id}", "FilesController@deleteFile");
});
