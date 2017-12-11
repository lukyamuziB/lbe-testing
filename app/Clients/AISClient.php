<?php

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Exceptions\NotFoundException;

/**
 * Class AISClient makes API calls to AIS service
 *
 * @package App\Clients
 */
class AISClient
{
    protected $api_url;
    protected $authorization_token;
    protected $client;

    /**
     * AISClient constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->api_url = getenv('AIS_API_URL');
        $this->authorization_token = getenv('AIS_API_TOKEN');
    }


    /**
     * GET a single user by id
     *
     * @param string $id - user's id
     *
     * @throws NotFoundException
     *
     * @return json $user JSON object containing targeted user
     */
    public function getUserById($id)
    {
        try {
            $response = $this->client->request(
                'GET', $this->api_url.'/users/'.$id,
                [
                "headers" => ["api-token" => $this->authorization_token],
                "verify" => false
                ]
            );
            $user = json_decode($response->getbody(), true);
            return $user;
        } catch (ClientException $exception) {
                throw new NotFoundException("user not found");
        }
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
        $response = $this->client->request(
            'GET', $this->api_url.'/users?emails='.$email,
            [
                "headers" => ["api-token" => $this->authorization_token],
                "verify" => false
            ]
        );

        $user = json_decode($response->getbody(), true);

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
        $limit = count($emails);
        $response = $this->client->request(
            'GET', $this->api_url.'/users?emails='.join(",", $emails).'&limit='.$limit,
            [
                "headers" => ["api-token" => $this->authorization_token],
                "verify" => false
            ]
        );

        $users = json_decode($response->getbody(), true);

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
        $response = $this->client->request(
            'GET', $this->api_url.'/users?name='.$name.'&limit='.$limit,
            [
                "headers" => ["api-token" => $this->authorization_token],
                "verify" => false
            ]
        );

        $users = json_decode($response->getbody(), true);

        return $users;
    }
}
