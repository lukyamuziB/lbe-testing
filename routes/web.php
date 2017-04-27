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
$app->group(['prefix' => 'api/v1'], function($app)
{
    $app->get('requests', 'RequestController@all');
    $app->get('requests/{id}', 'RequestController@get');
    $app->post('requests', 'RequestController@add');
    $app->put('requests/{id}', 'RequestController@put');
    $app->delete('requests/{id}', 'RequestController@remove');
});

/**
 * Routes for resource skills
 */
$app->group(['prefix' => 'api/v1'], function($app)
{
    $app->get('skills', 'SkillController@all');
    $app->get('skills/{id}', 'SkillController@get');
    $app->post('skills', 'SkillController@add');
    $app->put('skills/{id}', 'SkillController@put');
    $app->delete('skills/{id}', 'SkillController@remove');
});

/**
 * Routes for status
 */
$app->group(['prefix' => 'api/v1'], function($app)
{
    $app->get('status', 'StatusController@all');
});