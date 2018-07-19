<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\SuggestedSessionRescheduleRepository;

class SuggestedSessionRescheduleProvider extends ServiceProvider
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
        $this->app->bind(
            SuggestedSessionRescheduleRepository::class,
            function () {
                return new SuggestedSessionRescheduleRepository();
            }
        );
    }
}
