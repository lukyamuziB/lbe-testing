<?php
namespace App\Http\Controllers\V1;

use App\Exceptions\AccessDeniedException;
use App\Exceptions\NotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Request as MentorshipRequest;
use App\Clients\FreckleClient;
use App\Models\Session;
use Carbon\Carbon;
use Mockery\Exception;
use Illuminate\Support\Facades\Mail;
use App\Mail\FailedFreckleLoggingMail;
use GuzzleHttp\Exception\TransferException;

class SessionController extends Controller
{
    use RESTActions;

    protected $freckle_client;

    /**
     * SessionController constructor.
     *
     * @param FreckleClient $freckle_client
     */
    public function __construct(FreckleClient $freckle_client)
    {
        $this->freckle_client = $freckle_client;
    }

    /**
     * Get all the logged sessions of a particular request
     * including sessions logged by both mentee & mentor,
     * sessions pending to be logged.
     *
     * @param Request $request - request payload
     * @param String $id - id of the mentorship request
     * @throws NotFoundException
     * @return Response object containing session details
     */
    public function getSessionsReport(Request $request, $id)
    {
        $data = [];
        try {
            $mentorship_request = MentorshipRequest::findOrFail(intval($id));
            $data["sessions"] = $this->getSessionsByRequestId($mentorship_request->id);
            if ($request->input('include')) {
                $includes = explode(",", $request->input('include'));
                if (in_array('totalSessions', $includes)) {
                    $data["totalSessions"] = $this->getTotalSessions(
                        $mentorship_request->duration,
                        count($mentorship_request->pairing['days'])
                    );
                }
                if (in_array('totalSessionsLogged', $includes)) {
                    $data["totalSessionsLogged"] = $this->getTotalSessionsLogged($mentorship_request->id);
                }
                if (in_array('totalSessionsPending', $includes)) {
                    $data["totalSessionsPending"] = $this->getTotalSessionsPending($mentorship_request->id);
                }
                if (in_array('totalSessionsUnlogged', $includes)) {
                    $data["totalSessionsUnlogged"] = $this->getTotalSessionsUnlogged(
                        $mentorship_request->id,
                        $mentorship_request->duration,
                        count($mentorship_request->pairing['days'])
                    );
                }
            }
            $response = ["data" => $data];
            return $this->respond(Response::HTTP_OK, $response);
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }
    }

    /**
     * Get all existing logged sessions of a particular request
     *
     * @param $request_id
     * @return Array - array containing all sessions for a
     * particular request
     */
    public function getSessionsByRequestId($request_id)
    {
        $request_sessions = Session::withCount('rating')->where('request_id', $request_id)->get();
        return $request_sessions;
    }

    /**
     * Get the total number of sessions a request can have
     *
     * @param $request_duration
     * @param $request_days
     * @return Number - total sessions of a request which is a
     * multiple of the duration of the request and the number of
     * selected days in a week
     */
    public function getTotalSessions($request_duration, $request_days)
    {
        $total_sessions = ($request_duration * 4) * $request_days;
        return $total_sessions;
    }

    /**
     * Get total number of sessions logged both by mentor and mentee
     *
     * @param $request_id
     * @return Number - sessions completed or sessions logged
     * both by mentor and mentee
     */
    public function getTotalSessionsLogged($request_id)
    {
        $sessions_logged = Session::where('request_id', $request_id)
            ->where('mentee_approved', true)
            ->where('mentor_approved', true)
            ->count();
        return $sessions_logged;
    }


    /**
     * Get total number of sessions logged by either mentor and mentee
     * that have not been approved
     *
     * @param $requestId - the id of the request
     *
     * @return Number - sessions pending to be logged by both mentor and mentee
     */
    public function getTotalSessionsPending($requestId)
    {
        $pendingSessions = Session::where(
            function ($query) use ($requestId) {
                                        $query->where('mentee_approved', true)
                                            ->where('mentor_approved', null)
                                            ->where('request_id', $requestId);
            }
        )
            ->orWhere(
                function ($query) use ($requestId) {
                    $query->where('mentee_approved', null)
                        ->where('mentor_approved', true)
                        ->where('request_id', $requestId);
                }
            )
            ->count();
        return $pendingSessions;
    }



    /**
     * Get all the number of remaining unlogged sessions for a particular
     * request duration
     *
     * @param $request_id
     * @param $request_duration
     * @param $request_days
     * @return Number - a difference between the total number of sessionsCompleted
     * for a given request and its completed sessions
     */
    public function getTotalSessionsUnlogged($request_id, $request_duration, $request_days)
    {
        $total_sessions = $this->getTotalSessions($request_duration, $request_days);

        $total_sessions_logged = $this->getTotalSessionsLogged($request_id);
        $total_sessions_unlogged = $total_sessions - $total_sessions_logged;

        return $total_sessions_unlogged;
    }

    /**
     * Log completed sessions
     *
     * @param Request $request - request payload
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @return object - session object that has just been logged
     */
    public function logSession(Request $request)
    {
        try {
            $mentorship_request = MentorshipRequest::findOrFail(intval($request->input('request_id')));
            $user_id = $request->input('user_id');
            $date = $request->input('date');
            $start_time = $request->input('start_time');
            $end_time = $request->input('end_time');
            $request_id = $mentorship_request->id;
            $timezone = $mentorship_request->pairing['timezone'];
            $session_date = Carbon::createFromTimestamp($date, $timezone);

            $sessions_logged = $this->getSessionByRequestIdAndDate(
                $request_id,
                $session_date
            );
            if (sizeof($sessions_logged)) {
                return $this->respond(
                    Response::HTTP_CONFLICT,
                    ["message" => "Session already logged"]
                );
            }
            $approver = [];
            if ($user_id === $mentorship_request->mentor_id) {
                $approver["mentor_approved"] = true;
                $approver["mentor_logged_at"] = Carbon::now($timezone);
            } elseif ($user_id === $mentorship_request->mentee_id) {
                $approver["mentee_approved"] = true;
                $approver["mentee_logged_at"] = Carbon::now($timezone);
            } else {
                throw new AccessDeniedException(
                    "You do not have permission to log a session for this request"
                );
            }

            $session_logged = Session::create(
                array_merge(
                    [
                        "request_id" => $request_id,
                        "date" => $session_date->format('Y-m-d'),
                        "start_time" => $start_time,
                        "end_time" => $end_time
                    ],
                    $approver
                )
            );
            $response = ["data" => $session_logged];
            return $this->respond(Response::HTTP_CREATED, $response);
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("The specified mentor request was not found");
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }
    }

    /**
     * Check whether a session of a given request has already been logged
     * for a given date
     *
     * @param Number $request_id - id of request whose sessions are to be logged
     * @param $date
     * @return Array - containing the session object that was logged at that date
     */
    private function getSessionByRequestIdAndDate($request_id, $date)
    {
        return Session::where("request_id", $request_id)
            ->whereDate('date', $date)
            ->get();
    }

    /**
     * Update existing logged session for either the mentee or mentor
     * that logs to confirm a completed session
     *
     * @param Request $request - request payload
     * @param String $id - id of the session to be updated
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @return object - session object that has been updated
     */
    public function approveSession(Request $request, $id)
    {
        try {
            $session = Session::findOrFail(intval($id));
            $mentorship_request = $session->request;
            $user_id = $request->input('user_id');
            $timezone = $mentorship_request->pairing['timezone'];
            $session_update_date = Carbon::now($timezone);

            if ($user_id === $mentorship_request->mentee_id) {
                $approver = ["mentee_approved" => true, "mentee_logged_at" => $session_update_date];
            } elseif ($user_id === $mentorship_request->mentor_id) {
                $approver = ["mentor_approved" => true, "mentor_logged_at" => $session_update_date];
            } else {
                throw new AccessDeniedException(
                    "You do not have permission to approve this session"
                );
            }
            //Send Sessions to Freckle
            $sessionApproved = $session->fill($approver)->save();
            if ($sessionApproved) {
                return $this->logSessionToFreckle($session, $mentorship_request);
            }
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("Session does not exist");
        } catch (Exception $exception) {
            $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }
    }

    /**
     * Update existing logged session for either the mentee or mentor
     * that logs to reject a session
     *
     * @param Request $request - request payload
     * @param String $id - id of the session to be updated
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @return object - session object that has been updated
     *
     */
    public function rejectSession(Request $request, $id)
    {
        $session = Session::find(intval($id));
        if ($session == null) {
            throw new NotFoundException("Session does not exist");
        }
        $mentorship_request = $session->request;
        $user_id = $request->input('user_id');
        $timezone = $mentorship_request->pairing['timezone'];
        $session_update_date = Carbon::now($timezone);

        if ($user_id === $mentorship_request->mentee_id) {
            $values = ["mentee_approved" => false, "mentee_logged_at" => $session_update_date];
        } elseif ($user_id === $mentorship_request->mentor_id) {
            $values = ["mentor_approved" => false, "mentor_logged_at" => $session_update_date];
        } else {
                throw new AccessDeniedException(
                    "You do not have permission to reject this session"
                );
        }
        $session->fill($values)->save();
        return $this->respond(Response::HTTP_OK, $session);
    }
 
    /**
     * Calculate the time difference and return result in minutes
     *
     * @param String $start_time - session start time
     * @param String $end_time - session end time
     *
     * @return String $duration- the time difference in minutes
     */
    public function timeDifference($start_time, $end_time)
    {
        $duration = abs(strtotime($start_time) - strtotime($end_time)) / 60;
        return $duration;
    }

    /**
     * Get the Freckle Project ID to log hours from .env file
     *
     * @return String - project id
     */
    private function getFreckleProjectID()
    {
        return getenv('FRECKLE_PROJECT_ID');
    }

    /**
     * Log mentorship session to mentor's freckle account
     *
     * @param Object $session - session being logged
     * @param Object $mentorship_request - request object whose sessions are
     * being logged
     * @return Object $response- response meessage
     */
    public function logSessionToFreckle($session, $mentorship_request)
    {
        try {
            $minutes = $this->timeDifference(
                $session->start_time,
                $session->end_time
            );
            $mentor_email =$mentorship_request->mentor->email;
            $response = ["data" => $session];
            $freckle_user = $this->freckle_client->getUserByEmail($mentor_email);

            if ($freckle_user) {
                $data = array(
                    "date"=>$session->date,
                    "user_id"=>$freckle_user[0]['id'],
                    "minutes"=>$minutes,
                    "description"=>$mentorship_request->description,
                    "project_id"=>(int) $this->getFreckleProjectID(),
                );

                $this->freckle_client->postEntry($data);
            } else {
                throw new TransferException();
            }
        } catch (TransferException $exception) {
            $this->sendUnregisteredFreckleUserMail($session, $mentor_email);
        };

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Notify unregistered freckle mentors of failed mentorship logging
     *
     * @param Object $session - session being logged
     * @param String $to_address - recipient email
     */
    private function sendUnregisteredFreckleUserMail($session, $to_address)
    {
        $lenken_base_url = getenv("LENKEN_FRONTEND_BASE_URL");
        $request_url = $lenken_base_url."/requests/".$session['request_id'];
        $date = $session->date;
        $session_details = array(
            "date"=>$date,
            "session_request_url"=>$request_url,
        );
        Mail::to($to_address)->send(
            new FailedFreckleLoggingMail($session_details)
        );
    }
}
