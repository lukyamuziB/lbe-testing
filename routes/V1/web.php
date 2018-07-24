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
    $router->get('requests', 'DeprecationMessageController@get');
    $router->get('requests/{id}', 'DeprecationMessageController@get');
    $router->post('requests', 'DeprecationMessageController@get');
    $router->put('requests/{id}', 'DeprecationMessageController@get');
    $router->patch('requests/{id}/update-interested', 'DeprecationMessageController@get');
    $router->patch('requests/{id}/update-mentor', 'DeprecationMessageController@get');
    $router->patch('requests/{id}/cancel-request', 'DeprecationMessageController@get');
    $router->delete('requests/{id}', 'DeprecationMessageController@get');
    $router->put("requests/{id}/extend-mentorship", "DeprecationMessageController@get");
    $router->patch("requests/{id}/approve-extension", "DeprecationMessageController@get");
    $router->patch("requests/{id}/reject-extension", "DeprecationMessageController@get");
});

/**
 * Routes for resource skills
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('skills', 'DeprecationMessageController@get');
    $router->get('skills/{id}', 'DeprecationMessageController@get');
    $router->post('skills', 'DeprecationMessageController@get');
    $router->put('skills/{id}', 'DeprecationMessageController@get');
    $router->delete('skills/{id}', 'DeprecationMessageController@get');
});

/**
 * Routes for status
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('status', 'DeprecationMessageController@get');
});

/**
 * Routes for user information
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('users/{id}', 'DeprecationMessageController@get');
});

/**
 * Routes for reports
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('reports', 'DeprecationMessageController@get');
    $router->get('reports/unmatched-requests', 'DeprecationMessageController@get');
    $router->group([ 'middleware' => 'admin'], function ($router) {
        $router->get('reports/inactive-mentorships', 'ReportController@getInactiveMentorshipsReport');
    });
});

/**
 * Routes for messages
 */
$router->group(['prefix' => 'api/v1/messages'], function ($router) {
    $router->post('slack/send', 'DeprecationMessageController@get');
});

/**
 * Routes for sessions
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->get('sessions/{id}', 'DeprecationMessageController@get');
    $router->post('sessions', 'DeprecationMessageController@get');
    $router->patch('sessions/{id}/approve', 'DeprecationMessageController@get');
});

/**
 * Routes for resource session-rating
 */
$router->group(['prefix' => 'api/v1'], function ($router) {
    $router->post('ratings', 'DeprecationMessageController@get');
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
