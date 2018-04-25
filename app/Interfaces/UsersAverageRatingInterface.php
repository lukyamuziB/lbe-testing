<?php

namespace App\Interfaces;

/**
 * Interface RepositoryInterface
 *
 * @package App\Interfaces
 */
interface UsersAverageRatingInterface
{
    /**
     * Instantiating Redis
     *
     * @return object
     */
    public function make();

    /**
     * Get a record by id
     *
     * @param mixed $id record id
     * @return object record
     */
    public function getById($id);

    /**
     * Query records by specified parameters
     *
     * @param array $param a key value pair of key and value for query
     * @return array
     */
    public function query($param);
}
