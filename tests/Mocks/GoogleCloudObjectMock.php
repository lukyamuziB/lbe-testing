<?php

namespace Test\Mocks;

/**
 * Class GoogleCloudObjectMock
 *
 * @package Test\Mocks
 */
class GoogleCloudObjectMock
{
    protected $name;
    protected $file;
    protected $destination;
    protected $deleted;
    protected $signedUrl;
    /**
     * GoogleCloudObjectClientMock constructor.
     *
     * @param \Google\Cloud\Storage\Connection\ConnectionInterface $filename
     * @param string $file
     */
    public function __construct($filename, $file = "")
    {
        $this->name = $filename;
        $this->file = $file;
    }

    /**
     * Download file to destination mock
     *
     * @param $destination
     */
    public function downloadToFile($destination)
    {
        $this->destination = $destination;
    }

    /**
     * Delete file
     *
     * @internal param $destination
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * Return signed url to file
     *
     */
    public function signedUrl()
    {
        $this->signedUrl = "http://dummy/url";
        return $this->signedUrl;
    }
}
