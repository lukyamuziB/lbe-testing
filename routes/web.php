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
$app->get('requests', 'RequestsController@all');
$app->get('requests/{id}', 'RequestsController@get');
$app->post('requests', 'RequestsController@add');
$app->put('requests/{id}', 'RequestsController@put');
$app->delete('requests/{id}', 'RequestsController@remove');
