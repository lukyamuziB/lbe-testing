<?php

require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/../app/Helpers/GoogleCalendarHelper.php";

try {
    (new Dotenv\Dotenv(__DIR__."/../"))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__."/../")
);

$app->instance("path.storage", app()->basePath() . DIRECTORY_SEPARATOR . "storage");

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->instance(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    new Nord\Lumen\ChainedExceptionHandler\ChainedExceptionHandler(
        new App\Exceptions\Handler(),
        [new Nord\Lumen\NewRelic\NewRelicExceptionHandler()]
    )
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\ExampleMiddleware::class,
    \Barryvdh\Cors\HandleCors::class,
    Nord\Lumen\NewRelic\NewRelicMiddleware::class,
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'admin' => App\Http\Middleware\AdminMiddleware::class,
    'cors' => \Barryvdh\Cors\HandleCors::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(\Illuminate\Redis\RedisServiceProvider::class);
$app->register(Barryvdh\Cors\ServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);
$app->register(App\Providers\SlackServiceProvider::class);
$app->register(\App\Providers\FreckleServiceProvider::class);
$app->register(App\Providers\SlackUsersRepositoryProvider::class);
$app->register(App\Providers\GoogleClientServiceProvider::class);
$app->register(App\Providers\GoogleStorageClientServiceProvider::class);
$app->register(Nord\Lumen\NewRelic\NewRelicServiceProvider::class);
$app->configure("cors");
$app->configure("mail");
$app->configure("redis");
$app->configure("slack");
$app->configure("notifications");
$app->configure("services");

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group(["namespace" => "App\Http\Controllers\V1"], function ($router) {
    require __DIR__."/../routes/V1/web.php";
});

$app->router->group(["namespace" => "App\Http\Controllers\V2"], function ($router) {
    require __DIR__."/../routes/V2/web.php";
});

return $app;
