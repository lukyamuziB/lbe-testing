<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AISServiceProvider extends ServiceProvider
{

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Clients\AISClient', function ($app) {
            return new AISClient($app);
        });
    }
}