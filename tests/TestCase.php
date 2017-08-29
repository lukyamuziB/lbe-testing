<?php
use \Laravel\Lumen\Testing\DatabaseMigrations;
use Test\Mocks\FreckleClientMock;

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{

    use DatabaseMigrations;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function setUp()
    {
        parent::setUp();

        $this->artisan('db:seed');

        $freckle_client_mock = new FreckleClientMock();

        $this->app->instance('App\Clients\FreckleClient', $freckle_client_mock);
    }
}
