<?php
namespace App\Http\Controllers\V2;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Models\File;
use App\Models\Session;
use App\Models\Request as MentorshipRequest;
use App\Utility\FilesUtility;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

/**
 * Class SessionController
 *
 * @package App\Http\Controllers
 */
class SessionController extends Controller
{
    use RESTActions;

    protected $filesUtility;

    /**
     * SessionController constructor.
     *
     * @param FilesUtility $filesUtility
     */
    public function __construct(FilesUtility $filesUtility)
    {
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
        $sessions = Session::where("request_id", $id)
            ->with("files")
            ->get();

        return $this->respond(Response::HTTP_OK, $sessions);
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

    /**
     * Return missed, completed and upcoming sessions for a matched request
     *
     * @param $requestId - mentorship request id
     *
     * @throws NotFoundException
     *
     * @return object - Http response object
     */
    public function getSessionDates($requestId)
    {
        $requestDetails = MentorshipRequest::find($requestId);
        if (!$requestDetails) {
            throw new NotFoundException();
        }

        $mentorshipEndDate = Carbon::parse($requestDetails->match_date)->addMonth($requestDetails->duration);
        $mentorshipStartDate = Carbon::parse($requestDetails->match_date);
        $pairingDays = $requestDetails->pairing["days"];

        $sessionsDates = [];

        $allSessionDates = $this->generateAllSessionDates(
            $mentorshipStartDate,
            $mentorshipEndDate,
            $pairingDays
        );

        $loggedSessionsDates = $requestDetails->getLoggedSessionDates();

        foreach ($allSessionDates as $sessionDate) {
            if (in_array($sessionDate, $loggedSessionsDates)) {
                $sessionsDates[] = (Object)["date" => $sessionDate, "status" => "completed"];
            } elseif (Carbon::parse($sessionDate)->lt(Carbon::now())) {
                $sessionsDates[] = (Object)["date" => $sessionDate, "status" => "missed"];
            } elseif (Carbon::parse($sessionDate)->gt(Carbon::now())) {
                $sessionsDates[] = (Object)["date" => $sessionDate, "status" => "upcoming"];
            }
        }

        return $this->respond(Response::HTTP_OK, $sessionsDates);
    }

    /**
     * Return all sessions since the start date to today for request.
     *
     * @param $startDate - request match date.
     * @param $mentorshipEndDate - end date for a request.
     * @param $days - week days for sessions.
     *
     * @return array - session dates
     */
    private function generateAllSessionDates($startDate, $mentorshipEndDate, $days)
    {
        $allSessionDates = [];
        for ($date = $startDate; $date->lt($mentorshipEndDate); $date->addDay()) {
            if (in_array(strtoLower($date->format("l")), $days)) {
                $allSessionDates[] = $date->toDateString();
            }
        }
        return $allSessionDates;
    }
}
