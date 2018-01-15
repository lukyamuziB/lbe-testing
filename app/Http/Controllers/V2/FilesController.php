<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Utility\FilesUtility;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\File;

/**
 * Class FilesController
 *
 * @package App\Http\Controllers
 */
class FilesController extends Controller
{
    use RESTActions;

    private $filesUtility;

    /**
     * FilesController constructor.
     *
     * @param FilesUtility $filesUtility
     */
    public function __construct(FilesUtility $filesUtility)
    {
        $this->filesUtility = $filesUtility;
    }

    /**
     * Download a file from Cloud Storage and save it as a local file.
     *
     * @param $id - file id
     *
     * @throws NotFoundException
     *
     * @return object a file object
     */
    public function downloadFile($id)
    {
        $file = File::find($id);
        if (!$file) {
            throw new NotFoundException("File not found.");
        }

        $response = (object) [
            "id" => $file->id,
            "name" => $file->name,
            "url" =>$this->filesUtility->getFileUrl($file->generated_name, $file->name),
            "createdAt" => $file->created_at->toDatetimeString(),
            "updatedAt" => $file->updated_at->toDatetimeString(),
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Upload a file.
     *
     * @param Request $request - request object
     *
     * @throws BadRequestException
     *
     * @return Response Object
     */
    public function uploadFile(Request $request)
    {
        $uploadedFile = $request->file("file");
        if (!$uploadedFile) {
            throw new BadRequestException("File not found");
        }
        $fileName = $uploadedFile->getClientOriginalName();

        $file = new File();
        $file->name = $fileName;
        $file->generated_name = $this->filesUtility->storeFile($uploadedFile);

        $file->save();

        return $this->respond(Response::HTTP_CREATED);
    }

    /**
     * Delete a file from cloud storage.
     *
     * @param $id - file id
     *
     * @throws NotFoundException
     * @throws \Exception
     *
     * @return Response object
     */
    public function deleteFile($id)
    {
        $file = File::find($id);
        if (!$file) {
            throw new NotFoundException("File not found.");
        }

        $generatedName = $file->generated_name;
        $this->filesUtility->deleteFile($generatedName);

        $file->delete();

        return $this->respond(Response::HTTP_OK);
    }
}
