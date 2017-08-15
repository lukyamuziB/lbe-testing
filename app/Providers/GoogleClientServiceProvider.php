<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Clients\GoogleCalendarClient;

class GoogleClientServiceProvider extends ServiceProvider
{

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(App\Clients\GoogleCalendarClient::class, function ($app) {
            return new GoogleCalendarClient($app);
        });
    }
}
