<?php

namespace App\Providers;

use App\Clients\GoogleCloudStorageClient;
use Illuminate\Support\ServiceProvider;

class GoogleStorageClientServiceProvider extends ServiceProvider
{

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Interfaces\GoogleCloudStorageInterface', 'App\Clients\GoogleCloudStorageClient');
    }
}
