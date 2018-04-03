<?php
use \Laravel\Lumen\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Test\Mocks\FreckleClientMock;
use Test\Mocks\SlackUtilityMock;
use Test\Mocks\SlackUsersRepositoryMock;
use Test\Mocks\AISClientMock;
use Test\Mocks\GoogleCloudStorageClientMock;

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

        $this->artisan('migrate');

        $this->artisan('db:seed');

        $freckle_client_mock = new FreckleClientMock();

        $this->app->instance('App\Clients\FreckleClient', $freckle_client_mock);

        $slack_user_repository_mock = new SlackUsersRepositoryMock();

        $slack_utility_mock = new SlackUtilityMock($slack_user_repository_mock);

        $ais_client_mock = new AISClientMock();

        $google_cloud_storage_mock = new GoogleCloudStorageClientMock();

        $this->app->instance("App\Clients\GoogleCloudStorageClient", $google_cloud_storage_mock);

        $this->app->instance("App\Utility\SlackUtility", $slack_utility_mock);

        $this->app->instance("App\Clients\AISClient", $ais_client_mock);

        $this->app->instance("App\Repositories\SlackUsersRepository", $slack_user_repository_mock);

        Mail::fake();
    }

    /**
     * Configure and execute the command
     *
     * @param Application $application console application
     * @param string      $signature   the command signature
     * @param string      $class_name  the command class name
     *
     * @return object $command_tester
     */
    protected function executeCommand(
        Application $application,
        $signature,
        $class_name
    ) {
        $command = $this->app->make($class_name);

        $command->setLaravel(app());

        $application->add($command);

        $command_signature = $application->find($signature);

        $command_tester = new CommandTester($command_signature);

        $command_tester->execute(
            [ "command" => $signature]
        );

        return $command_tester;
    }

    public function tearDown()
    {
        DB::rollback();
        parent::tearDown();
    }
}
