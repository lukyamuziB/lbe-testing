<?php
namespace Test\Mocks;

use App\Clients\AISClient;

use PHPUnit\Runner\Exception;
use App\Exceptions\NotFoundException;

/**
 * Class AISClientMock makes mock API calls to AIS service
 *
 * @package App\Clients
 */
class AISClientMock extends AISClient
{

    public function __construct()
    {
        $this->make();
    }

    private $model;

    /**
     * Populate the model with users
     */
    private function make()
    {
        $this->model = [
            [
                "id" => "-K_nkl19N6-EGNa0W8LF",
                "email" => "adebayo.adesanya@andela.com",
                "name" => "Adebayo Adesanya",
                "location" => "Lagos",
                "picture" => "picture",
                "first_name" => "Adebayo",
                "last_name" => "Adesanya",
                "cohort" => null,
                "roles" => "fellow",
                "placement" =>  ["client" => "Available", "status" =>"Available"],
                "level" => null
            ],
            [
                "id" => "-KXGy1MimjQgFim7u",
                "email" => "inumidun.amao@andela.com",
                "name" => "Inumidun Amao",
                "location" => "Lagos",
                "picture" => "picture",
                "first_name" => "Inumidun",
                "last_name" => "Amao",
                "cohort" => "cohort15",
                "roles" => "fellow",
                "placement" => ["client" => "google", "status" => "External Engagements - Standard"],
                "level" => "D1"

            ],
            [
                "id" => "-KXGyddsds2imjQgFim7u",
                "email" => "ichiato.ikkin@andela.com",
                "name" => "Ichiato Ikkin",
                "location" => "Lagos",
                "picture" => "picture",
                "first_name" => "Ichiato",
                "last_name" => "Ikkin",
                "cohort" => "cohort15",
                "roles" => "fellow",
                "placement" =>  ["client" => "Available", "status" =>"Available"],
                "level" => "D1"
            ],
            [
                "id" => "-KXGy1MTiQgFim7",
                "email" => "felistas.ngunmi@andela.com",
                "name" => "Felistas Ngunmi",
                "location" => "Nairobi",
                "picture" => "picture",
                "first_name" => "Felistas",
                "last_name" => "Ngumi",
                "cohort" => "cohort15",
                "roles" => "fellow",
                "placement" => ["client" => "google", "status" => "External Engagements - Standard"],
                "level" => "D1"
            ],
            [
                "id" => "-K1MTimjQgFim7u",
                "email" => "faith.omakaro@andela.com",
                "name" => "Faith Omokaro",
                "location" => "Lagos",
                "picture" => "picture",
                "first_name" => "Faith",
                "last_name" => "Omokaro",
                "cohort" => "cohort15",
                "roles" => "fellow",
                "placement" => ["client" => "google", "status" => "External Engagements - Standard"],
                "level" => "D1"
            ],
            [
                "id" => "-KXGywq1ew-eTimjQgFim7u",
                "email" => "chinazor.allen@andela.com",
                "name" => "Chinazor Allen",
                "location" => "Lagos",
                "picture" => "picture",
                "first_name" => "Chinazor",
                "last_name" => "Allen",
                "cohort" => "cohort15",
                "roles" => "fellow",
                "placement" => ["client" => "google", "status" =>"External Engagements - Standard"],
                "level" => "D1"
            ],
            [
                "id" => "-KXGy1MTimjQgFim7u",
                "email" => "daisy.wanjiru@andela.com",
                "name" => "Daisy Wanjiru",
                "location" => "Nairobi",
                "picture" => "picture",
                "first_name" => "Daisy",
                "last_name" => "Wanjiru",
                "cohort" => "cohort15",
                "roles" => "fellow",
                "placement" => ["client" => "Available", "status" =>"Available"],
                "level" => "D1"
            ],
            [
                "id" => "-KXGy1MT1oimjQgFim7u",
                "email" => "timothy.kyadondo@andela.com",
                "name" => "Timothy Kyadondo",
                "location" => "Kampala",
                "picture" => "picture",
                "first_name" => "Timothy",
                "last_name" => "Kyadondo",
                "cohort" => "cohort2",
                "roles" => "fellow",
                "placement" => ["client" => "Available", "status" =>"Available"],
                "level" => "D1"
            ],
            [
                "id" => "-KcYSwKNhJbZtOkk9ciS",
                "email" => "temi.lajumoke@andela.com",
                "name" => "Temi Lajumoke",
                "location" => "Kampala",
                "picture" => "picture",
                "first_name" => "Temi",
                "last_name" => "Lajumoke",
                "cohort" => "cohort2",
                "roles" => "fellow",
                "placement" => ["client" => "Available", "status" =>"Available"],
                "level" => "D1"
            ]
        ];
    }

    /**
     * GET a single user by id
     *
     * @param string $id - user's id
     *
     * @return json $user JSON object containing targeted user
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function getUserById($id)
    {
        $user = array_values(array_filter($this->model, function ($user) use ($id) {
                return $user["id"] === $id;
        }));

        if (count($user) > 0) {
            return $user[0];
        }

        throw new NotFoundException("User not found.");
    }

    /**
     * GET a single user by the email address
     *
     * @param string $email - user's email
     *
     * @return json $user JSON object containing targeted user
     */
    public function getUserByEmail($email)
    {
        $user = array_values(array_filter($this->model, function ($user) use ($email) {
            return $user["email"] === $email;
        }))[0];
        return $user;
    }

    /**
     * GET a multiple users by multiple emails provided
     *
     * @param array $emails - list of user's emails
     *
     * @return json $users JSON object containing all targeted users
     */
    public function getUsersByEmail($emails)
    {
        $users = [];

        foreach ($emails as $email) {
            foreach ($this->model as $user) {
                if ($user["email"] === $email) {
                    $users[] = $user;
                }
            }
        }

        return [
            "values" => count($users) ? $users : null,
            "total" => count($users)
        ];
    }

    /**
     * GET a multiple users by a name
     *
     * @param string $name - user's name
     *
     * @param integer $limit - limit of results returned
     *
     * @return json $users JSON object containing all targeted users
     */
    public function getUsersByName($name, $limit = 10)
    {
        $users = [];

        $count = count($name) > $limit ? $limit : count($name);
        for ($i = 0; $i < $count; $i++) {
            array_push($users, array_values(array_filter($this->model, function ($user) use ($name, $i) {
                return $user["name"] === $name[$i];
            }))[0]);
        }

        return $users;
    }
}
