<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SlackServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Utility\Slack', function ($app) {
            return new Slack($app);
        });
    }
}

