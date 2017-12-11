<?php

namespace App\Clients;

use Google\Cloud\Storage\StorageClient;

/**
 * Class GoogleCloudStorageClient makes API calls to GCP service.
 *
 * @package App\Clients
 */
class GoogleCloudStorageClient
{
    protected $bucket;

    /**
     * GoogleCloudStorageClient constructor.
     */
    public function __construct()
    {
        $storageClient = new StorageClient();
        $bucketName = getenv("GOOGLE_CLOUD_STORAGE_BUCKET");
        $this->bucket = $storageClient->bucket($bucketName);
    }

    /**
     * Get an object by name from Google cloud Storage.
     *
     * @param $fileName - generated unique name
     *
     * @return \Google\Cloud\Storage\StorageObject
     */
    public function getFileByName($fileName)
    {
        return $this->bucket->object($fileName);
    }

    /**
     * Upload an object on Google Cloud Storage.
     *
     * @param $file - file object to be stored
     * @param $fileName - storage name
     *
     * @return void
     */
    public function uploadFile($file, $fileName)
    {
        $this->bucket->upload($file, [
            "name" => $fileName,
        ]);
    }

    /**
     * Delete an object on Google Cloud Storage.
     *
     * @param $fileName - unique name by which the file is stored with
     *
     * @return void
     */
    public function deleteFile($fileName)
    {
        $this->bucket->object($fileName)->delete();
    }
}
