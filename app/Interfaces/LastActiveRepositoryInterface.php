<?php

namespace App\Interfaces;

/**
 * Interface LastActiveRepositoryInterface
 *
 * @package App\Interfaces
 */
interface LastActiveRepositoryInterface
{
    /**
     * Sets a last active record by id
     *
     * @param mixed $id last active record id
     * @param mixed $time last active record time
     *
     * @return void
     */
    public function set($id, $time);

    /**
     * Gets a single last active record by id
     *
     * @return string a record
     */
    public function get($id);

    /**
     * Query records by specified parameters
     *
     * @param array $param array of keys for query
     * @return array
     */
    public function query($param);
}
