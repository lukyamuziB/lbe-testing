<?php

namespace App\Interfaces;

/**
 * Interface GoogleCloudStorageInterface
 *
 * @package App\Interfaces
 */
interface GoogleCloudStorageInterface
{
    /**
     * Get an object by name from Google cloud storage.
     *
     * @param $fileName - generated unique name
     *
     * @return \Google\Cloud\Storage\StorageObject
     */
    public function getFileByName($fileName);

    /**
     * Store an object on Google Cloud Storage.
     *
     * @param $file
     * @param $fileName
     *
     * @return \Google\Cloud\Storage\Bucket
     */
    public function uploadFile($file, $fileName);

    /**
     * Delete an object on Google Cloud Storage.
     *
     * @param $fileName
     *
     * @return void
     */
    public function deleteFile($fileName);
}
