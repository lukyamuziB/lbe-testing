<?php
namespace App\Http\Controllers\V2;

use DB;
use Exception;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Models\File;
use App\Models\Session;
use App\Models\Request as MentorshipRequest;
use App\Models\SessionComment;
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
     * @return \Illuminate\Http\JsonResponse and the file object
     *
     */
    public function uploadSessionFile(Request $request, $id)
    {
        $uploadedFile = $request->file("file");
        $optionalFileName = $request->input('name');

        $fileName = $optionalFileName ?? $uploadedFile->getClientOriginalName();

        $file = new File();
        $file->name = $fileName;
        $file->generated_name = $this->filesUtility->storeFile($uploadedFile);

        $file->save();

        $session = Session::find($id);
        $session->files()->save($file);

        return $this->respond(Response::HTTP_CREATED, $file);
    }


    /**
     * Get all sessions of a request from the day the session started
     * to today and the next upcoming session
     *
     * @param $requestId - mentorship request id
     *
     * @throws NotFoundException
     *
     * @return object - HttpResponse object
     */
    public function getRequestSessions($requestId)
    {
        $request = MentorshipRequest::find($requestId);
        if (!$request) {
            throw new NotFoundException("Mentorship Request not found.");
        }

        $mentorshipEndDate = Carbon::parse($request->match_date)->addMonth($request->duration);
        $mentorshipStartDate = Carbon::parse($request->match_date);
        $pairingDays = $request->pairing["days"];

        $sessions = [];

        $allSessionDates = $this->generateAllSessionDates(
            $mentorshipStartDate,
            $mentorshipEndDate,
            $pairingDays
        );

        $loggedSessions = $request->getLoggedSessions();
        $today = Carbon::now();

        foreach ($allSessionDates as $sessionDate) {
            if (Carbon::parse($sessionDate)->lte($today)) {
                $session = Session::findSessionByDate($loggedSessions, $sessionDate);

                if (count((array)$session) > 0) {
                    $sessions[] = (object)[
                        "date" => $sessionDate,
                        "status" => ($session->mentee_approved && $session->mentor_approved) ? "completed" : "missed",
                        "mentee_logged" => $session->mentee_approved,
                        "mentor_logged" => $session->mentor_approved,
                        "files"=>$session->files
                    ];
                } else {
                    $sessions[] = (object)[
                        "date" => $sessionDate,
                        "status" => "missed",
                        "mentee_logged" => false,
                        "mentor_logged" => false,
                        "files"=>[]
                    ];
                }
            } else {
                $sessions[] = (object)[
                    "date" => $sessionDate,
                    "status" => "upcoming",
                    "mentee_logged" => false,
                    "mentor_logged" => false,
                    "files"=>[]
                ];
                break;
            }
        }

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
        $today = Carbon::now();

        foreach ($allSessionDates as $sessionDate) {
            if (in_array($sessionDate, $loggedSessionsDates)) {
                $sessionsDates[] = (Object)["date" => $sessionDate, "status" => "completed"];
            } elseif (Carbon::parse($sessionDate)->lte($today)) {
                $sessionsDates[] = (Object)["date" => $sessionDate, "status" => "missed"];
            } elseif (Carbon::parse($sessionDate)->gt($today)) {
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

    /**
     * Log a session for a Mentorship Request.
     *
     * @param Request $request - HttpRequest object
     *
     * @throws AccessDeniedException|NotFoundException
     *
     * @return object - session object that has just been logged
     */
    public function logSession(Request $request, $requestId)
    {
        $mentorshipRequest = MentorshipRequest::find(intval($requestId));
        if (!$mentorshipRequest) {
            throw new NotFoundException("Mentorship Request not found.");
        }

        $date = $request->input("date");
        $startTime = $request->input("start_time");
        $endTime = $request->input("end_time");
        $timezone = $mentorshipRequest->pairing["timezone"];
        $sessionDate = Carbon::createFromTimestamp($date, $timezone);

        $loggedSession = Session::getSessionByRequestIdAndDate(
            $requestId,
            $sessionDate
        );

        if (count($loggedSession) > 0) {
            return $this->respond(
                Response::HTTP_CONFLICT,
                ["message" => "Session already logged."]
            );
        }

        $userId = $request->user()->uid;
        $approvalStatus = [];

        if ($userId === $mentorshipRequest->mentor_id) {
            $approvalStatus["mentor_approved"] = true;
            $approvalStatus["mentor_logged_at"] = Carbon::now($timezone);
        } elseif ($userId === $mentorshipRequest->mentee_id) {
            $approvalStatus["mentee_approved"] = true;
            $approvalStatus["mentee_logged_at"] = Carbon::now($timezone);
        } else {
            return $this->respond(
                Response::HTTP_FORBIDDEN,
                ["message" => "You do not have permission to log a session for this request."]
            );
        }

        $result = DB::transaction(
            function () use ($requestId, $sessionDate, $startTime, $endTime, $approvalStatus, $request, $userId) {
                $session = Session::create(
                    array_merge(
                        [
                            "request_id" => $requestId,
                            "date" => $sessionDate->format('Y-m-d'),
                            "start_time" => $startTime,
                            "end_time" => $endTime
                        ],
                        $approvalStatus
                    )
                );

                if ($comment = $request->input("comment")) {
                    $session->comment = SessionComment::create([
                        "user_id" => $userId,
                        "session_id" => $session->id,
                        "comment" => $comment
                    ]);
                }

                return $session;
            }
        );
        return $this->respond(Response::HTTP_CREATED, $result);
    }
}
