<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Interfaces\RepositoryInterface;
use Illuminate\Support\Facades\Redis;

class SlackUsersRepository implements RepositoryInterface
{
    protected $model;

    /**
     * SlackUsersRepositoryMock constructor.
     */
    public function __construct()
    {
        $this->make();
    }

    /**
     * Populate $model from Redis cache or make it an empty array if cache is empty
     */
    public function make()
    {
        $key = "slack:allUsers";

        $this->model = Redis::exists($key) ? json_decode(Redis::get($key)) : [];
    }

    /**
     * Gets a slack user by slack id
     *
     * @param integer $id slack id
     *
     * @return object|null slack user or null if the user does not exist
     */
    public function getById($id)
    {
        return $this->model[$id] ?? null;
    }

    /**
     * Gets all slack users
     *
     * @return array all slack users
     */
    public function getAll()
    {
        return $this->model;
    }

    /**
     * Gets a slack user by email
     *
     * @param string $email email address of the user
     *
     * @return object|null $slackUser the slack user or null if it doesn't exist
     */
    public function getByEmail($email)
    {
        $slack_user = null;

        foreach ($this->model as $record) {
            if ($record->email === $email) {
                $slack_user = $record;
                break;
            }
        }

        return $slack_user;
    }

    /**
     * Gets a slack user by handle
     *
     * @param string $handle slack handle of the user
     *
     * @return object|null $slackUser the slack user or null if it doesn't exist
     */
    public function getByHandle($handle)
    {
        $slack_user = null;
        foreach ($this->model as $record) {
            if ($record->handle === $handle) {
                $slack_user = $record;
                break;
            }
        }

        return $slack_user;
    }

    /**
     * Queries the model with specified parameters
     *
     * @param array $param parameters
     *
     * @return array
     */
    public function query($param)
    {
        // TODO: Implement query() method.
    }
}