<?php

namespace Test\Mocks;

use App\Clients\GoogleCloudStorageClient;

/**
 * Class GoogleCloudObjectMock
 *
 * @package Test\Mocks
 */
class GoogleCloudStorageClientMock extends GoogleCloudStorageClient
{

    /**
     * Get file by name.
     *
     * @param $fileName
     *
     * @return \Google\Cloud\Storage\StorageObject|object
     */
    public function getFileByName($fileName)
    {
        $file = new GoogleCloudObjectMock($fileName);
        return $file;
    }

    /**
     * Upload file.
     *
     * @param $file
     * @param $fileName
     *
     * @return \Google\Cloud\Storage\StorageObject|object
     */
    public function uploadFile($file, $fileName)
    {
        $file = new GoogleCloudObjectMock($fileName, $file);
        return $file;
    }

    /**
     * Delete file from Google cloud storage.
     *
     * @param $fileName
     *
     * @return void
     */
    public function deleteFile($fileName)
    {
        $file = new GoogleCloudObjectMock($fileName);
        $file->delete();
    }
}
