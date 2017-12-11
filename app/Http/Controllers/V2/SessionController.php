<?php
namespace App\Http\Controllers\V2;

use App\Clients\GoogleCloudStorageClient;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Models\File;
use App\Models\Session;
use App\Utility\FilesUtility;
use Doctrine\DBAL\Query\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class SessionController
 *
 * @package App\Http\Controllers
 */
class SessionController extends Controller
{
    use RESTActions;

    protected $googleCloudStorageClient;
    protected $filesUtility;

    /**
     * SessionController constructor.
     *
     * @param GoogleCloudStorageClient $googleCloudStorageClient
     * @param FilesUtility $filesUtility
     */
    public function __construct(GoogleCloudStorageClient $googleCloudStorageClient, FilesUtility $filesUtility)
    {
        $this->googleCloudStorageClient = $googleCloudStorageClient;
        $this->filesUtility = $filesUtility;
    }

    /**
     * Add new files that belong to a session.
     *
     * @param Request $request - request object
     * @param $id - session id
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function uploadSessionFile(Request $request, $id)
    {
        $uploadedFile = $request->file("file");
        $fileName = $uploadedFile->getClientOriginalName();

        $file = new File();
        $file->name = $fileName;

        $file->generated_name = $this->filesUtility->storeFile($uploadedFile);

        $file->save();

        $session = Session::find($id);
        $session->files()->save($file);

        return $this->respond(Response::HTTP_CREATED);
    }

    /**
     * Fetch all file details belonging to a session.
     *
     * @param $id - request id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSessions($id)
    {
        $sessions = Session::select(
            'id',
            'request_id as requestID',
            'date',
            'start_time as startTime',
            'end_time as endTime',
            'mentee_approved as menteeApproved',
            'mentor_approved as mentorApproved',
            'mentee_logged_at as menteeLoggedAt',
            'mentor_logged_at as mentorLoggedAt'
        )
            ->where("request_id", $id)
            ->get();

        $response = [];
        foreach ($sessions as $session) {
            $sessionFiles = $session->files()->get();

            foreach ($sessionFiles as &$sessionFile) {
                $sessionFile->url = $this->filesUtility->getFileUrl($sessionFile->generated_name);
            }
            $session->files = $sessionFiles;

            $response[] = $session;
        }

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Delete a session file from cloud storage.
     *
     * @param $id - session id
     * @param $fileId - file id
     *
     * @throws \Exception
     * @return Response
     *
     */
    public function deleteSessionFile($id, $fileId)
    {
        $session = Session::find($id);
        $file = File::find($fileId);

        $sessionCount = $file->sessions()->count();
        $session->files()->detach($fileId);

        if ($sessionCount == 1) {
            $file->delete();
            $this->filesUtility->deleteFile($file->generated_name);
        }

        return $this->respond(Response::HTTP_OK);
    }

    /**
     * Attach a session file to a session.
     *
     * @param Request $request - request object
     * @param $id session id
     *
     * @throws BadRequestException
     * @throws NotFoundException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachSessionFile(Request $request, $id)
    {
        $fileId = $this->getRequestParams($request, "fileId");
        if (!$fileId) {
            throw new BadRequestException("file id not provided");
        }
        if (!File::find($fileId)) {
            throw new NotFoundException("file not found");
        }
        $session = Session::find($id);
        if (!$session) {
            throw new NotFoundException("session not found");
        }
        $session->files()->attach($fileId);

        return $this->respond(Response::HTTP_OK, ["message" => "File attached"]);
    }

    /**
     * Return details for session file pivot table.
     *
     * @param Request $request - request object
     * @param $id - session id
     *
     * @throws BadRequestException
     * @throws NotFoundException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detachSessionFile(Request $request, $id)
    {
        $fileId = $this->getRequestParams($request, "fileId");
        if (!$fileId) {
            throw new BadRequestException("file id not provided");
        }
        if (!File::find($fileId)) {
            throw new NotFoundException("file not found");
        }
        $session = Session::find($id);
        if (!$session) {
            throw new NotFoundException("session not found");
        }
        $session->files()->detach($fileId);

        return $this->respond(Response::HTTP_OK);
    }
}
