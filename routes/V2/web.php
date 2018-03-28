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
$router->get("/", function () use ($router) {
    return $router->app->version();
});

/**
 * Routes for  requests
 */
$router->group(["prefix" => "api/v2"], function ($router) {
    $router->get("requests/pool", "RequestController@getRequestsPool");
    $router->get("requests", "RequestController@getAllRequests");
    $router->get("requests/history", "RequestController@getUserHistory");
    $router->get("requests/in-progress", "RequestController@getRequestsInProgress");
    $router->get("requests/pending", "RequestController@getPendingPool");
    $router->patch("requests/{id}/cancel-request", "RequestController@cancelRequest");
    $router->patch("requests/{id}/withdraw-interest", "RequestController@withdrawInterest");
    $router->patch("requests/{id}/indicate-interest", "RequestController@indicateInterest");
    $router->get("requests/{id}/sessions", "SessionController@getRequestSessions");
    $router->patch("requests/{id}/accept-user", "RequestController@acceptInterestedUser");
    $router->patch("requests/{id}/reject-user", "RequestController@rejectInterestedUser");
    $router->group(["middleware" => "admin"], function ($router) {
        $router->get("requests/status-statistics", "ReportController@getRequestsStatusStatistics");
    });
    $router->get("requests/{id}", "RequestController@getRequest");
    $router->post("requests", "RequestController@createRequest");
});
/**
 * Routes for skills
 */
$router->group(["prefix" => "api/v2"], function ($router) {
    $router->get("skills/request-skills", "SkillController@getSkillsWithRequests");
    $router->post("users/{userId}/skills", "SkillController@addUserSkill");
    $router->delete("users/{userId}/skills/{skillId}", "SkillController@deleteUserSkill");
    $router->get("skills", "SkillController@getSkills");
    $router->group(["middleware" => "admin"], function ($router) {
        $router->get('skill/status-report', 'SkillController@getSkillsAndStatusCount');
        $router->patch("skills/{skillId}/update-status", "SkillController@updateSkillStatus");
        $router->post('skills', 'SkillController@addSkill');
    });
    $router->get('skill/status-report', 'SkillController@getSkillsAndStatusCount');
});
/**
 * Routes for users
 */
$router->group(["prefix" => "api/v2"], function ($router) {
    $router->get("users", "UserController@getUsersByIds");
    $router->get("users/{id}", "UserController@get");
    $router->post("users/{userId}/skills", "UserController@addUserSkill");
    $router->delete("users/{userId}/skills/{skillId}", "UserController@deleteUserSkill");
});

/**
 * Routes for sessions
 */
$router->group(["prefix" => "api/v2"], function ($router) {
    $router->post("sessions/{id}/files", "SessionController@uploadSessionFile");
    $router->delete("sessions/{id}/files/{fileId}", "SessionController@deleteSessionFile");
    $router->patch("sessions/{id}/attach", "SessionController@attachSessionFile");
    $router->patch("sessions/{id}/detach", "SessionController@detachSessionFile");
    $router->patch("sessions/{id}/confirm", "SessionController@confirmSession");
});

/**
 * Routes for request sessions
 */
$router->group(["prefix" => "api/v2"], function ($router) {
    $router->get("requests/{id}/sessions/dates", "SessionController@getSessionDates");
    $router->patch("requests/{id}/sessions/{sessionId}", "SessionController@logSession");
    $router->post("requests/{id}/sessions", "SessionController@createSession");
});

/**
 * Routes for files manipulation
 */
$router->group(["prefix" => "api/v2"], function ($router) {
    $router->get("files/{id}", "FilesController@downloadFile");
    $router->post("files", "FilesController@uploadFile");
    $router->delete("files/{id}", "FilesController@deleteFile");
});
