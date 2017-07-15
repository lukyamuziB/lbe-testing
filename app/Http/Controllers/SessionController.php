<?php
namespace App\Http\Controllers;
use App\Exceptions\NotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Request as MentorshipRequest;
use App\Models\Session;
use Carbon\Carbon;
use Mockery\Exception;

class SessionController extends Controller
{
    use RESTActions;
    /**
     * Get all the logged sessions of a particular request
     * including sessions logged by both mentee & mentor,
     * sessions pending to be logged.
     *
     * @param Object $request - request payload
     * @param String $id - id of the mentorship request
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
     * @param object $mentorship_request - request object whose
     * sessions are to be logged
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
     * @param object $mentorship_request - request object whose
     * sessions are to be logged
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
     * @param object $mentorship_request - request object whose
     * sessions are to be logged
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
     *
     * @param object $mentorship_request - request object whose
     * sessions are to be logged
     * @return Number - sessions pending to be logged by both mentor and mentee
     */
    public function getTotalSessionsPending($request_id)
    {
        $sessions_pending = Session::where('request_id', $request_id)
          ->where(function ($query) {
            $query->where('mentee_approved', true)->where('mentor_approved', null);
          })->orWhere(function ($query) {
            $query->where('mentee_approved', null)->where('mentor_approved', true);
          })->count();
        return $sessions_pending;
    }
    /**
     * Get all the number of remaining unlogged sessions for a particular
     * request duration
     *
     * @param object $mentorship_request - request object whose
     * sessions are to be logged
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
     * @param object $request - request payload
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
                $request_id, $session_date
            );
            if (sizeof($sessions_logged)) {
                return $this->respond(Response::HTTP_CONFLICT,
                    ["message" => "Session already logged"]);
            }
            $approver = [];
            if ($user_id === $mentorship_request->mentor_id) {
                $approver["mentor_approved"] = true;
                $approver["mentor_logged_at"] = Carbon::now($timezone);
            } else {
                $approver["mentee_approved"] = true;
                $approver["mentee_logged_at"] = Carbon::now($timezone);
            }
            $session_logged = Session::create(array_merge(
                [
                    "request_id" => $request_id,
                    "date" => $session_date->format('Y-m-d'),
                    "start_time" => $start_time,
                    "end_time" => $end_time
                ],
                $approver)
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
     * @param Carbon instance $date - date when session is logged
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
     * @param object $request - request payload
     * @param String | Number $id - id of the session to be updated
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
            }
            $session->fill($approver)->save();
            $response = ["data" => $session];
            return $this->respond(Response::HTTP_OK, $response);
        } catch (ModelNotFoundException $exception) {
            throw new NotFoundException("Session does not exist");
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }
    }
}
