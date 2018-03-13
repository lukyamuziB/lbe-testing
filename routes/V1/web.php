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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

/**
 * Routes for resource requests
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('requests', 'RequestController@all');
    $router->get('requests/{id}', 'RequestController@get');
    $router->post('requests', 'RequestController@add');
    $router->put('requests/{id}', 'RequestController@update');
    $router->patch('requests/{id}/update-interested', 'RequestController@updateInterested');
    $router->patch('requests/{id}/update-mentor', 'RequestController@updateMentor');
    $router->patch('requests/{id}/cancel-request', 'RequestController@cancelRequest');
    $router->delete('requests/{id}', 'RequestController@remove');
    $router->put("requests/{id}/extend-mentorship", "RequestController@requestExtension");
    $router->patch("requests/{id}/approve-extension", "RequestController@approveExtensionRequest");
    $router->patch("requests/{id}/reject-extension", "RequestController@rejectExtensionRequest");
});

/**
 * Routes for resource skills
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('skills', 'SkillController@all');
    $router->get('skills/{id}', 'SkillController@get');
    $router->post('skills', 'SkillController@add');
    $router->put('skills/{id}', 'SkillController@put');
    $router->delete('skills/{id}', 'SkillController@remove');
});

/**
 * Routes for status
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('status', 'StatusController@all');
});

/**
 * Routes for user information
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('users/{id}', 'UserController@get');
});

/**
 * Routes for reports
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('reports', 'ReportController@all');
    $router->get('reports/unmatched-requests', 'ReportController@getUnmatchedRequests');
    $router->group([ 'middleware' => 'admin'], function ($router) {
        $router->get('reports/inactive-mentorships', 'ReportController@getInactiveMentorshipsReport');
    });
});

/**
 * Routes for messages
 */
$router->group(['prefix' => 'api/v1/messages'], function ($router) {
    $router->post('slack/send', 'SlackController@sendMessage');
});

/**
 * Routes for sessions
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('sessions/{id}', 'SessionController@getSessionsReport');
    $router->post('sessions', 'SessionController@logSession');
    $router->patch('sessions/{id}/approve', 'SessionController@approveSession');
    $router->patch('sessions/{id}/reject', 'SessionController@rejectSession');
});

/**
 * Routes for resource session-rating
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->post('ratings', 'RatingController@rateSession');
});

/**
 * Routes for notification
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('notifications', 'NotificationController@all');
    $router->post('notifications', 'NotificationController@add');
    $router->put('notifications/{id}', 'NotificationController@put');
    $router->delete('notifications/{id}', 'NotificationController@delete');
    $router->get('user/{user_id}/settings', 'NotificationController@getNotificationsByUserId');
    $router->put('user/{user_id}/settings/{id}', 'NotificationController@updateUserSettings');
});
