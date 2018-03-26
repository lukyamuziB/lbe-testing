<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Clients\FreckleClient;

class FreckleServiceProvider extends ServiceProvider
{

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Clients\FreckleClient', function ($app) {
            return new FreckleClient($app);
        });
    }
}
