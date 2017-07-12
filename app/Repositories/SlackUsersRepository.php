<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Interfaces\RepositoryInterface;
use Illuminate\Support\Facades\Redis;

class SlackUsersRepository implements RepositoryInterface
{
    private $model;

    /**
     * SlackUsersRepository constructor.
     */
    public function __construct()
    {
        $this->make();
    }

    /**
     * Populate $model from Redis cache
     */
    public function make()
    {
        $key = "slack:allUsers";

        if (Redis::exists($key)) {
            $this->model = json_decode(Redis::get($key));
        } else {
            throw new RepositoryException("Redis cache is not populated");
        }
    }

    /**
     * Gets a slack user by slack id
     *
     * @param integer $id slack id
     *
     * @return object slack user
     */
    public function getById($id)
    {
        return $this->model[$id];
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
        $slack_user = new \stdClass();

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