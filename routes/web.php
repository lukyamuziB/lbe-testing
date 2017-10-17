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

$app->get('/', function () use ($app) {
    return $app->version();
});

/**
 * Routes for resource requests
 */
$app->group(['prefix' => 'api/v1'], function ($app) {
    $app->get('requests', 'RequestController@all');
    $app->get('requests/{id}', 'RequestController@get');
    $app->post('requests', 'RequestController@add');
    $app->put('requests/{id}', 'RequestController@update');
    $app->patch('requests/{id}/update-interested', 'RequestController@updateInterested');
    $app->patch('requests/{id}/update-mentor', 'RequestController@updateMentor');
    $app->patch('requests/{id}/cancel-request', 'RequestController@cancelRequest');
    $app->delete('requests/{id}', 'RequestController@remove');
    $app->put("requests/{id}/extend-mentorship", "RequestController@requestExtension");
    $app->patch("requests/{id}/approve-extension", "RequestController@approveExtensionRequest");
    $app->patch("requests/{id}/reject-extension", "RequestController@rejectExtensionRequest");
});

/**
 * Routes for resource skills
 */
$app->group(['prefix' => 'api/v1'], function ($app) {
    $app->get('skills', 'SkillController@all');
    $app->get('skills/{id}', 'SkillController@get');
    $app->post('skills', 'SkillController@add');
    $app->put('skills/{id}', 'SkillController@put');
    $app->delete('skills/{id}', 'SkillController@remove');
});

/**
 * Routes for status
 */
$app->group(['prefix' => 'api/v1'], function ($app) {
    $app->get('status', 'StatusController@all');
});

/**
 * Routes for user information
 */
$app->group(['prefix' => 'api/v1'], function ($app) {
    $app->get('users/{id}', 'UserController@get');
});

/**
 * Routes for reports
 */
$app->group(['prefix' => 'api/v1'], function ($app) {
    $app->get('reports', 'ReportController@all');
    $app->get('reports/unmatched-requests', 'ReportController@getUnmatchedRequests');
});

/**
 * Routes for messages
 */
$app->group(['prefix' => 'api/v1/messages'], function ($app) {
    $app->post('slack/send', 'SlackController@sendMessage');
});

/**
 * Routes for sessions
 */
$app->group(['prefix' => 'api/v1'], function ($app) {
    $app->get('sessions/{id}', 'SessionController@getSessionsReport');
    $app->post('sessions', 'SessionController@logSession');
    $app->patch('sessions/{id}/approve', 'SessionController@approveSession');
    $app->patch('sessions/{id}/reject', 'SessionController@rejectSession');
});

/**
 * Routes for resource session-rating
 */
$app->group(['prefix' => 'api/v1'], function ($app) {
    $app->post('ratings', 'RatingController@rateSession');
});

/**
 * Routes for notification
 */
$app->group(['prefix' => 'api/v1'], function ($app) {
    $app->get('notifications', 'NotificationController@all');
    $app->post('notifications', 'NotificationController@add');
    $app->put('notifications/{id}', 'NotificationController@put');
    $app->delete('notifications/{id}', 'NotificationController@delete');
    $app->get('user/{user_id}/settings', 'NotificationController@getNotificationsByUserId');
    $app->put('user/{user_id}/settings/{id}', 'NotificationController@updateUserSettings');
});
