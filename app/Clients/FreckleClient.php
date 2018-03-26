<?php

namespace App\Clients;

use GuzzleHttp\Client;

/**
 * Class FreckleClient makes API calls to Freckle API
 *
 * @package App\Clients
 */
class FreckleClient
{
    protected $client;
    protected $api_url;
    protected $freckle_token;

    /**
     * FreckleClient constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->api_url = getenv('FRECKLE_API_URL');
        $this->freckle_token = getenv('FRECKLE_API_TOKEN');
    }

    /**
     * Retrieve all entries
     *
     * @return array $entries list of all entries
     */
    public function getAllEntries()
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/entries',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $entries = json_decode($response->getbody(), true);

        return $entries;
    }

    /**
     * Retrieve a single entry
     *
     * @param int $id the entry id
     *
     * @return array $entry of an entry
     */
    public function getEntryById($id)
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/entries/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $entry = json_decode($response->getbody(), true);

        return $entry;
    }

    /**
     * Post a single entry
     *
     * @param array $data entry data i.e date, user_id, minutes, description, project_id and source_url
     *
     * @return array $entry of an entry
     */
    public function postEntry($data)
    {
        $response = $this->client->request(
            'POST',
            $this->api_url . '/entries',
            [
                "headers" => [
                    "X-FreckleToken" => $this->freckle_token
                ],
                "body" => json_encode($data),
                "verify" => false
            ]
        );

        $entry = json_decode($response->getbody(), true);

        return $entry;
    }

    /**
     * Edit a single entry
     *
     * @param int $id the entry id
     * @param array $data the entry data i.e date, user_id, minutes, description, project_id, project_name, source_url
     *
     * @return array $entry of an entry
     */
    public function putEntry($id, $data)
    {
        $response = $this->client->request(
            'PUT',
            $this->api_url . '/entries/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "body" => json_encode($data),
                "verify" => false
            ]
        );

        $entry = json_decode($response->getbody(), true);

        return $entry;
    }

    /**
     * Delete a single entry
     *
     * @param int $id the entry id
     *
     * @return json response Status: 204 No Content
     */
    public function deleteEntryById($id)
    {
        $response = $this->client->request(
            'DELETE',
            $this->api_url . '/entries/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );


        $status = json_decode($response->getbody(), true);

        return $status;
    }

    /**
     * Retrieve all tags
     *
     * @return array $tags list of tags
     */
    public function getAllTags()
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/tags',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $tags = json_decode($response->getbody(), true);

        return $tags;
    }

    /**
     * Retrieve a single tag
     *
     * @param int $id the tag id
     *
     * @return array $tag of a tag
     */
    public function getTagById($id)
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/tags/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $tag = json_decode($response->getbody(), true);

        return $tag;
    }

    /**
     * Post a single tag
     * Adding a “*” at the end of the tag name indicates that the tag is unbillable
     * A tag that cannot be created will be ignored and not affect the response
     * A tag that already exists will be ignored and not affect the response
     *
     * @param string $name the tag name
     *
     * @return array $tag of a tag
     */
    public function postTag($name)
    {
        $response = $this->client->request(
            'POST',
            $this->api_url . '/tags',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "name" => $name,
                "verify" => false
            ]
        );

        $tag = json_decode($response->getbody(), true);

        return $tag;
    }

    /**
     * Retrieve entries for a single tag
     *
     * @param int $id the tag id
     *
     * @return array $entries list of entries for a tag
     */
    public function getTagEntriesByTagId($id)
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/tags/' . $id . '/entries',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $entries = json_decode($response->getbody(), true);

        return $entries;
    }

    /**
     * Edit a single tag
     *
     * @param int $id the tag id
     * @param string $name the tag name
     *
     * @return array $tag of a tag
     */
    public function putTag($id, $name)
    {
        $response = $this->client->request(
            'PUT',
            $this->api_url . '/tags/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "name" => $name,
                "verify" => false
            ]
        );

        $tag = json_decode($response->getbody(), true);

        return $tag;
    }

    /**
     * Delete a single tag
     *
     * @param int $id the tag id
     *
     * @return json response Status: 204 No Content
     */
    public function deleteTagById($id)
    {
        $response = $this->client->request(
            'DELETE',
            $this->api_url . '/tags/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $status = json_decode($response->getbody(), true);

        return $status;
    }

    /**
     * Retrieve all projects the authenticated user has access to
     *
     * @return array $projects list of projects
     */
    public function getAllProjects()
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/projects',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $projects = json_decode($response->getbody(), true);

        return $projects;
    }

    /**
     * Retrieve a single project
     *
     * @param int $id the project id
     *
     * @return array $project of a project
     */
    public function getProjectById($id)
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/projects/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $project = json_decode($response->getbody(), true);

        return $project;
    }

    /**
     * Post a single project
     *
     * @param string $name the project name
     *
     * @return array $project of a project
     */
    public function postProject($name)
    {
        $response = $this->client->request(
            'POST',
            $this->api_url . '/projects',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "name" => $name,
                "verify" => false
            ]
        );

        $project = json_decode($response->getbody(), true);

        return $project;
    }

    /**
     * Retrieve entries for a single project
     *
     * @param int $id the project id
     *
     * @return array $entries list of entries for the project
     */
    public function getProjectEntriesById($id)
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/projects/' . $id . '/entries',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $entries = json_decode($response->getbody(), true);

        return $entries;
    }

    /**
     * Edit a single project
     *
     * @param int $id the project if
     * @param string $name the project name
     *
     * @return array $project of a project
     */
    public function putProject($id, $name)
    {
        $response = $this->client->request(
            'PUT',
            $this->api_url . '/projects/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "name" => $name,
                "verify" => false
            ]
        );

        $project = json_decode($response->getbody(), true);

        return $project;
    }

    /**
     * Retrieve the Freckle Account details
     *
     * @return array $account of an account
     */
    public function getAccountDetails()
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/account/',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $account = json_decode($response->getbody(), true);

        return $account;
    }

    /**
     * Retrieve all users
     *
     * @return array $users list of users
     */
    public function getAllUsers()
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/users',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $users = json_decode($response->getbody(), true);

        return $users;
    }

    /**
     * Retrieve specific users using email address
     *
     * @param string $email_address the user's email address
     *
     * @return array $user of a user
     */
    public function getUserByEmail($email_address)
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/users?email=' . $email_address,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $user = json_decode($response->getbody(), true);

        return $user;
    }

    /**
     * Retrieve specific users using id
     *
     * @param int $id the user's id
     *
     * @return array $user of a user
     */
    public function getUserById($id)
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/users/' . $id,
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $user = json_decode($response->getbody(), true);

        return $user;
    }

    /**
     * Retrieve specific user's entries
     *
     * @param int $id the user's id
     *
     * @return array $entries of a user
     */
    public function getUserEntriesById($id)
    {
        $response = $this->client->request(
            'GET',
            $this->api_url . '/users/' . $id . '/entries',
            [
                "headers" => ["X-FreckleToken" => $this->freckle_token],
                "verify" => false
            ]
        );

        $entries = json_decode($response->getbody(), true);

        return $entries;
    }

    /**
     * Post a single user
     *
     * @param array $data users data i.e email, first_name, last_name, role
     *
     * @return array $user of a user
     */
    public function postUser($data)
    {
        $response = $this->client->request(
            'POST',
            $this->api_url . '/users',
            [
                "headers" => [
                    "X-FreckleToken" => $this->freckle_token
                ],
                "body" => json_encode($data),
                "verify" => false
            ]
        );

        $user = json_decode($response->getbody(), true);

        return $user;
    }

    /**
     * Edit a single user
     *
     * @param int $id the user id
     * @param array $data users data i.e id, first_name, last_name, role
     *
     * @return array $user of a user
     */
    public function putUser($id, $data)
    {
        $response = $this->client->request(
            'PUT',
            $this->api_url . '/users/' . $id,
            [
                "headers" => [
                    "X-FreckleToken" => $this->freckle_token
                ],
                "body" => json_encode($data),
                "verify" => false
            ]
        );

        $user = json_decode($response->getbody(), true);

        return $user;
    }
}
