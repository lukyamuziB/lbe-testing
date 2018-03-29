<?php
namespace App\Providers;

use App\Repositories\LastActiveRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Class LastActiveRepositoryProvider
 *
 * @package App\Providers
 */
class LastActiveRepositoryProvider extends ServiceProvider
{
    /**
     * Register binding in the container.
     */
    public function register()
    {
        $this->app->bind(
            LastActiveRepository::class,
            function () {
                return new LastActiveRepository();
            }
        );
    }
}
