<?php
namespace App\Utility;

use App\Clients\GoogleCloudStorageClient;
use Carbon\Carbon;

class FilesUtility
{
    protected $googleCloudStorageClient;

    public function __construct(GoogleCloudStorageClient $googleCloudStorageClient)
    {
        $this->googleCloudStorageClient = $googleCloudStorageClient;
    }

    /**
     * Get url of a file from cloud storage given a name.
     *
     * @param $name - file name
     * @param DateTime $expiryDate
     *
     * @return string
     */
    public function getFileUrl($name, $saveAs = null, $expiryDate = null)
    {
        $googleCloudFile = $this->googleCloudStorageClient->getFileByName($name);
        $timestamp = $expiryDate ?? Carbon::tomorrow();

        $url = $googleCloudFile->signedUrl($timestamp, ["method"=>"GET", "saveAsName"=> $saveAs ?? $name]);

        return str_replace('"', '', $url);
    }

    /**
     * Store file to google cloud.
     *
     * @param $uploadedFile
     *
     * @return string
     */
    public function storeFile($uploadedFile)
    {
        $path = $uploadedFile->getRealPath();

        $generatedName = uniqid();

        $physicalFile = fopen($path, 'r');
        $this->googleCloudStorageClient->uploadFile($physicalFile, $generatedName);

        return $generatedName;
    }

    /**
     * Delete file from cloud storage.
     *
     * @param $name - generated file name
     *
     * @return void
     */
    public function deleteFile($name)
    {
        $this->googleCloudStorageClient->deleteFile($name);
    }
}
