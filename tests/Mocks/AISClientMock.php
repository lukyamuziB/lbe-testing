<?php

namespace Test\Mocks;

use App\Clients\AISClient;
use PHPUnit\Runner\Exception;

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
                "name" => "Adebayo Adesanya"
            ],
            [
                "id" => "-KXGy1MimjQgFim7u",
                "email" => "inumidun.amao@andela.com",
                "name" => "Inumidun Amao"
            ],
            [
                "id" => "-KXGyddsds2imjQgFim7u",
                "email" => "ichiato.ikkin@andela.com",
                "name" => "Ichiato Ikkin"
            ],
            [
                "id" => "-KXGy1MTiQgFim7",
                "email" => "felistas.ngunmi@andela.com",
                "name" => "Felistas Ngunmi"
            ],
            [
                "id" => "-K1MTimjQgFim7u",
                "email" => "faith.omakaro@andela.com",
                "name" => "Faith Omokaro"
            ],
            [
                "id" => "-KXGywq1ew-eTimjQgFim7u",
                "email" => "chinazor.allen@andela.com",
                "name" => "Chinazor Allen"
            ],
            [
                "id" => "-KXGy1MTimjQgFim7u",
                "email" => "daisy.wanjiru@andela.com",
                "name" => "Daisy Wanjiru"
            ]
        ];
    }

    /**
     * GET a single user by id
     *
     * @param string $id - user's id
     *
     * @return json $user JSON object containing targeted user
     */
    public function getUserById($id)
    {
        $user = array_values(array_filter($this->model, function ($user) use ($id) {
            return $user["id"] === $id;
        }))[0];

        return $user;
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
     * @param integer $limit - limit of results returned
     *
     * @return json $users JSON object containing all targeted users
     */
    public function getUsersByEmail($emails, $limit = 10)
    {
        $users = [];

        $count = count($emails) > $limit ? $limit : count($emails);
        for ($i = 0; $i < $count; $i++) {
            array_push($users, array_values(array_filter($this->model, function ($user) use ($emails, $i) {
                return $user["email"] === $emails[$i];
            }))[0]);
        }

        return $users;
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
