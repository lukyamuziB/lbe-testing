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
use Test\Mocks\LastActiveRepositoryMock;
use Test\Mocks\UsersAverageRatingMock;

use App\Models\Request;
use App\Models\RequestUsers;
use App\Models\Role;
use App\Models\RequestType;

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

        $freckleClientMock = new FreckleClientMock();

        $this->app->instance('App\Clients\FreckleClient', $freckleClientMock);

        $slackUserRepositoryMock = new SlackUsersRepositoryMock();

        $lastActiveRepositoryMock = new LastActiveRepositoryMock();

        $usersAverageRatingMock = new UsersAverageRatingMock();

        $slackUtilityMock = new SlackUtilityMock($slackUserRepositoryMock);

        $aisClientMock = new AISClientMock();

        $googleCloudStorageMock = new GoogleCloudStorageClientMock();

        $this->app->instance("App\Clients\GoogleCloudStorageClient", $googleCloudStorageMock);

        $this->app->instance("App\Utility\SlackUtility", $slackUtilityMock);

        $this->app->instance("App\Clients\AISClient", $aisClientMock);

        $this->app->instance("App\Repositories\SlackUsersRepository", $slackUserRepositoryMock);

        $this->app->instance("App\Repositories\LastActiveRepository", $lastActiveRepositoryMock);

        $this->app->instance("App\Repositories\UsersAverageRatingRepository", $usersAverageRatingMock);

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

    /**
     * Creates a request with the status id provided as argument
     *
     * @param string $createdBy  - The ID of the user making a request
     * @param string $interested - The ID of the user interested
     * @param int    $statusId   - The status of the request.
     *
     * @return void
     */
    public function createRequest($createdBy, $title, $interested, $statusId, $createdAt = "2017-09-19 20:55:24")
    {
        $createdRequest = Request::create(
            [
                "created_by" => $createdBy,
                "request_type_id" => RequestType::MENTEE_REQUEST,
                "title" => $title,
                "description" => "Learn JavaScript",
                "status_id" => $statusId,
                "created_at" => $createdAt,
                "match_date" => null,
                "interested" => [$interested],
                "duration" => 2,
                "pairing" => (
                    [
                        "start_time" => "01:00",
                        "end_time" => "02:00",
                        "days" => ["monday"],
                        "timezone" => "EAT"
                    ]
                ),
                "location" => "Nairobi"
            ]
        );

        RequestUsers::create(
            [
                "user_id" => $createdBy,
                "role_id" => Role::MENTEE,
                "request_id" => $createdRequest["id"]
            ]
        );

        return $createdRequest;
    }

    public function tearDown()
    {
        DB::rollback();
        parent::tearDown();
    }
}
