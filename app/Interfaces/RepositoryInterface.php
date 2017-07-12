<?php

namespace App\Interfaces;

/**
 * Interface RepositoryInterface
 *
 * @package App\Interfaces
 */
interface RepositoryInterface
{
    /**
     * Populate the model from data source
     *
     * @return array
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
     * Get all records
     *
     * @return array all records
     */
    public function getAll();

    /**
     * Query records by specified parameters
     *
     * @param array $param a key value pair of key and value for query
     * @return array
     */
    public function query($param);




}