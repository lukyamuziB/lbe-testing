<?php
use \Laravel\Lumen\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;

use Test\Mocks\FreckleClientMock;
use Test\Mocks\SlackUtilityMock;
use Test\Mocks\SlackUsersRepositoryMock;
use Test\Mocks\AISClientMock;

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

        $slack_user_repository_mock = new SlackUsersRepositoryMock();

        $slack_utility_mock = new SlackUtilityMock($slack_user_repository_mock);

        $ais_client_mock = new AISClientMock();

        $this->app->instance("App\Utility\SlackUtility", $slack_utility_mock);

        $this->app->instance("App\Clients\AISClient", $ais_client_mock);

        Mail::fake();
    }
}
