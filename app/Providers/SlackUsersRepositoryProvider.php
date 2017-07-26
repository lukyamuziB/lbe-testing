<?php
namespace App\Providers;

use App\Repositories\SlackUsersRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Class SlackUsersRepositoryProvider
 *
 * @package App\Providers
 */
class SlackUsersRepositoryProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register binding in the container.
     */
    public function register()
    {
        $this->app->bind(
            SlackUsersRepository::class, function () {
                return new SlackUsersRepository();
            }
        );
    }
}