<?php

namespace App\Http\Controllers\V2;

use DB;
use App\Exceptions\ConflictException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Models\File;
use App\Models\Session;
use App\Models\Rating;
use App\Models\Role;
use App\Models\RequestType;
use App\Models\Request as MentorshipRequest;
use App\Models\SessionComment;
use App\Utility\FilesUtility;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use App\Clients\FreckleClient;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoggedSessionMail;
use App\Mail\ConfirmedSessionMail;
use App\Mail\FailedFreckleLoggingMail;
use App\Mail\SessionFileNotificationMail;

/**
 * Class SessionController
 *
 * @package App\Http\Controllers
 */
class SessionController extends Controller
{
    use RESTActions;

    protected $filesUtility;
    protected $freckleClient;

    /**
     * SessionController constructor.
     *
     * @param FilesUtility $filesUtility
     */
    public function __construct(FilesUtility $filesUtility, FreckleClient $freckleClient)
    {
        $this->filesUtility = $filesUtility;
        $this->freckleClient = $freckleClient;
    }

    /**
     * Add new files that belong to a session.
     *
     * @param Request $request - request object
     *
     *
     * @return \Illuminate\Http\JsonResponse and the file object with sessionId
     *
     */
    public function uploadSessionFile(Request $request, $sessionId)
    {
        $uploadedFile = $request->file("file");
        $optionalFileName = $request->input('name');
        $sessionId = (int)$sessionId;
        
        $fileName = $optionalFileName ?? $uploadedFile->getClientOriginalName();
        $file = new File();
        $file->name = $fileName;
        $file->generated_name = $this->filesUtility->storeFile($uploadedFile);
        $file->save();

        $session = Session::find($sessionId);
        $session->files()->save($file);

        $response = [
            "file" => $file,
            "session_id" => $session->id,
        ];

        $this->sendSessionFileNotification(
            $session,
            $fileName,
            $request->user()->uid,
            Session::UPLOAD_FILE
        );

        return $this->respond(Response::HTTP_CREATED, $response);
    }

    /**
     * Creates a session given request and requestId
     *
     * @param Request $request - request object
     * @param $requestId - request id
     *
     * @return \Illuminate\Http\JsonResponse and the session object
     */
    public function createSession(Request $request, $requestId)
    {
        $sessionDate = date($request->input("date"));
        $requestId = (int)$requestId;

        $request = MentorshipRequest::find($requestId);

        $session = new Session();
        $session->request_id = $requestId;
        $session->date = $sessionDate;
        $session->start_time = Carbon::parse($sessionDate . $request->pairing['start_time']);
        $session->end_time = Carbon::parse($sessionDate . $request->pairing['end_time']);
        $session->save();

        return $this->respond(Response::HTTP_CREATED, $session);
    }

    /**
     * Get a single session.
     *
     * @param $sessionId - The id of the session
     *
     *  @throws NotFoundException
     *
     * @return Object - object containing a single session
     */
    public function getSingleSession($sessionId)
    {
         $session = Session::where('id', $sessionId)
                            ->with("rating", "comments")
                            ->first();

        if (!$session) {
            throw new NotFoundException('Session not found.');
        }

        if ($session->rating) {
            $session->rating->values = json_decode($session->rating->values);
        }

        return $this->respond(Response::HTTP_OK, $session);
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
                        "id" => $session->id,
                        "date" => $sessionDate,
                        "start_time" => $session->start_time,
                        "end_time" => $session->end_time,
                        "status" => ($session->mentee_approved && $session->mentor_approved) ? "completed" : "missed",
                        "mentee_logged" => $session->mentee_approved,
                        "mentor_logged" => $session->mentor_approved,
                        "files" => $session->files
                    ];
                } else {
                    $sessions[] = (object)[
                        "id" => null,
                        "date" => $sessionDate,
                        "status" => "missed",
                        "mentee_logged" => false,
                        "mentor_logged" => false,
                        "files" => []
                    ];
                }
            } else {
                $sessions[] = (object)[
                    "id" => null,
                    "date" => $sessionDate,
                    "status" => "upcoming",
                    "mentee_logged" => false,
                    "mentor_logged" => false,
                    "files" => []
                ];
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
    public function deleteSessionFile(Request $request, $id, $fileId)
    {
        $session = Session::find($id);
        $file = File::find($fileId);
        $sessionCount = $file->sessions()->count();
        $session->files()->detach($fileId);

        if ($sessionCount == 1) {
            $file->delete();
            $this->filesUtility->deleteFile($file->generated_name);
        }

        $this->sendSessionFileNotification(
            $session,
            $file->name,
            $request->user()->uid,
            Session::DELETE_FILE
        );

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
     * Log or updates a session for a Mentorship Request.
     *
     * @param Request $request - HttpRequest object
     * @param $requestId - request id
     * @param $sessionId - session id
     *
     * @throws NotFoundException | AccessDeniedException
     *
     * @return object - session object that has just been logged
     */
    public function updateSession(Request $request, $sessionId, $requestId)
    {
        $mentorshipRequest = MentorshipRequest::find(intval($requestId));
        if (!$mentorshipRequest) {
            throw new NotFoundException("Mentorship Request not found.");
        }

        $currentUserId = $request->user()->uid;

        $startTime = $request->input("start_time");
        $endTime = $request->input("end_time");
        $timezone = $mentorshipRequest->pairing["timezone"];

        $approvalStatus = $this->getApprovalStatus($currentUserId, $mentorshipRequest, $timezone);


        $sessionApproval = array_merge(
            [
                "start_time" => $startTime,
                "end_time" => $endTime,
            ],
            $approvalStatus
        );

        $sessionToLog = Session::find(intval($sessionId));

        if (!$sessionToLog) {
            throw new NotFoundException("Session not found.");
        }

        $rating = [
            "values" => $request->input("rating_values"),
            "scale" => $request->input("rating_scale"),
            "comment" => $request->input("comment")
        ];

        $result = DB::transaction(
            function () use ($currentUserId, $mentorshipRequest, $sessionToLog, $sessionApproval, $rating) {

                //update session with the approval
                $sessionToLog->update($sessionApproval);

                $userToRate = ($mentorshipRequest->mentee->id === $currentUserId)
                    ? $mentorshipRequest->mentor->id
                    : $mentorshipRequest->mentee->id;

                $sessionToLog->saveComment($rating["comment"], $userToRate);
                if (($rating["values"])) {
                    $sessionToLog->saveRating($userToRate, $rating["values"], $rating["scale"]);
                }

                return $sessionToLog;
            }
        );

        $this->sendLoggedSessionNotification($sessionToLog->recipient, $sessionToLog);
        return $this->respond(Response::HTTP_CREATED, $result);
    }

    /**
     * Confirms a logged session.
     *
     * @param Request $request - HTTP Request object
     * @param $id - ID of the session to be confirmed
     *
     * @throws NotFoundException|ConflictException|AccessDeniedException|UnauthorizedException
     *
     * @return \Illuminate\Http\JsonResponse - Confirm session object
     */
    public function confirmSession(Request $request, $id)
    {
        $session = Session::with('request')->find((int)$id);
        if (!$session) {
            throw new NotFoundException("Session not found.");
        }
        $userId = $request->user()->uid;
        $timezone = $session->request->pairing["timezone"];
        $menteeId = $session->request->mentee->id ?? "";
        $mentorId = $session->request->mentor->id ?? "";
        $userRole = $userId === $mentorId ? "mentor" : "mentee";
        $confirmation = [];

        if ($userId !== $session->request[$userRole] ["id"]) {
            throw new UnauthorizedException("You do not have permission to confirm this session.");
        }

        if ($session[$userRole . "_approved"]) {
            throw new ConflictException("Session already confirmed.");
        }

        $confirmation[$userRole . "_approved"] = true;
        $confirmation[$userRole . "_logged_at"] = Carbon::now($timezone);

        $result = DB::transaction(
            function () use (
                $userId,
                $request,
                $confirmation,
                $menteeId,
                $mentorId,
                $session
            ) {
                $session->update($confirmation);
                $userToRate = ($userId === $menteeId) ? $mentorId : $menteeId;
                if ($comment = $request->input("comment")) {
                    $session->comment = SessionComment::create(
                        [
                            "user_id" => $userToRate,
                            "session_id" => $session->id,
                            "comment" => $comment
                        ]
                    );
                }

                if ($ratings = $request->input("ratings")) {
                    $session->ratings = Rating::create(
                        [
                            "user_id" => $userToRate,
                            "session_id" => $session->id,
                            "values" => json_encode($ratings),
                            "scale" => $request->input("rating_scale")
                        ]
                    );
                }

                return $session;
            }
        );

        if ($session->mentee_approved && $session->mentor_approved) {
            $this->logSessionOnFreckle($session);
        }

        $this->sendConfirmSessionNotification($session->recipient, $session);

        return $this->respond(Response::HTTP_OK, $result);
    }

    /**
     * Check the mentorship request and detemine if it's mentor or mentee approving the request.
     *
     * @param string $currentUserId - ID of the currently logged in user
     * @param MentorshipRequest $mentorshipRequest - the mentorship request for the session
     * @param string $timezone - the timezone of the mentorship request
     *
     * @throws AccessDeniedException
     *
     * @return mixed $approvalStatus - the approval status object for the request
     */
    private function getApprovalStatus($currentUserId, $mentorshipRequest, $timezone)
    {
        $approvalStatus = [];

        if ($currentUserId === $mentorshipRequest->mentor->id) {
            $approvalStatus["mentor_approved"] = true;
            $approvalStatus["mentor_logged_at"] = Carbon::now($timezone);
        } elseif ($currentUserId === $mentorshipRequest->mentee->id) {
            $approvalStatus["mentee_approved"] = true;
            $approvalStatus["mentee_logged_at"] = Carbon::now($timezone);
        } else {
             throw new AccessDeniedException("You do not have permission to log a session for this request.");
        }

        return $approvalStatus;
    }

    /**
     * Log mentorship session to mentor's freckle account
     *
     * @param Object $session - session being logged
     *
     */
    private function logSessionOnFreckle($session)
    {
        $duration = (strtotime($session->end_time) - strtotime($session->start_time)) / 60;
        $mentorEmail = $session->request->mentor->email;

        $freckleUser = $this->freckleClient->getUserByEmail($mentorEmail);

        if ($freckleUser) {
            $menteeName = str_replace(" ", "", $session->request->mentee->fullname);
            $this->freckleClient->postEntry(
                [
                    "date" => $session->date,
                    "user_id" => $freckleUser[0]["id"],
                    "minutes" => $duration,
                    "description" => "#mentorshipWith" . $menteeName,
                    "project_id" => intval(getenv("FRECKLE_PROJECT_ID"))
                ]
            );
        } else {
            $this->sendUnregisteredFreckleUserMail($session, $mentorEmail);
        }
    }

    /**
     * Notify unregistered freckle mentors of failed mentorship logging
     *
     * @param Object $session - session being logged
     * @param String $email   - recipient's email address
     *
     * @return void
     */
    private function sendUnregisteredFreckleUserMail($session, $email)
    {
        Mail::to($email)->send(
            new FailedFreckleLoggingMail($session->date)
        );
    }

    /**
     * Checks who should recieve a file upload notification,
     * composes the notification and then sends the
     * notification to the right user
     *
     * @param Object  $session        - the session
     * @param String  $fileName       - the filename
     * @param String  $userId         - current userId
     * @param Boolean $isFileUploaded - To check if a file is being uploaded or deleted 
     * 
     * @return Object - Email object
     */
    function sendSessionFileNotification(
        $session, 
        $fileName, 
        $userId, 
        $typeOfAction
    ) {
        $recipient = "";
        $notificationDetails = [
            "fileName" =>  $fileName,
            "sessionDate" => date("F jS, Y", strtotime($session->date)),
            "typeOfAction" => $typeOfAction
        ];

        if ($session->request->mentee->id === $userId) {
            $recipient = $session->request->mentor->email;
            $notificationDetails["requesterName"] = $session->request->mentee->fullname;
        } else {
            $recipient = $session->request->mentee->email;
            $notificationDetails["requesterName"] = $session->request->mentor->fullname;
        }
        $sessionFileEmailInstance = new SessionFileNotificationMail(
            $recipient, 
            $notificationDetails
        );

        return sendEmailNotificationBasedOnUserSettings($recipient, $sessionFileEmailInstance);
    }

    /**
     * Sends logged session notification
     *
     * @param String $recipient - Receiver of the mail
     * @param Object $payload - notification payload
     *
     * @return void
     */
    private function sendLoggedSessionNotification($recipient, $payload)
    {
        $lenkenBaseUrl = getenv("LENKEN_FRONTEND_BASE_URL");
        $sessionUrl = "$lenkenBaseUrl/request-pool/in-progress/$payload->request_id";
        $recipient = ($payload->mentee_approved) ?
            $payload->request->mentor->email: $payload->request->mentee->email;
        $loggedData = new LoggedSessionMail(
            [
            "sessionDate" => $payload->date,
            "currentUser" => ($payload->mentee_approved) ?
            $payload->request->mentee->fullname : $payload->request->mentor->fullname,
            "sessionDuration" => $payload->request->duration,
            "sessionUrl" => $sessionUrl
            ],
            $recipient
        );

        return sendEmailNotificationBasedOnUserSettings($recipient, $loggedData);
    }

    /**
     * Sends confirmed session notification
     *
     * @param String $recipient - Receiver of the mail
     * @param Object $payload - notification payload
     *
     * @return void
     */
    private function sendConfirmSessionNotification($recipient, $payload)
    {
        $recipient = ($payload->mentee_approved) ?
            $payload->request->mentor->email: $payload->request->mentee->email;
        $loggedData = new ConfirmedSessionMail(
            [
            "date" => $payload->date,
            "currentUser" => ($payload->mentee_approved) ?
            $payload->request->mentee->fullname : $payload->request->mentor->fullname,
            ],
            $recipient
        );

        return sendEmailNotificationBasedOnUserSettings($recipient, $loggedData);
    }
}
