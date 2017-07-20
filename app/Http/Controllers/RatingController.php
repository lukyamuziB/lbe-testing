<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use App\Models\Rating;
use App\Models\Request as MentorshipRequest;
use App\Models\Session;

class RatingController extends Controller
{
    use RESTActions;

    /**
     *  Mentee rates a specific session after logging it
     *
     * @param Request|object $request -request payload from the user
     * @return object - ratings object that has been created
     */
    public function rateSession(Request $request)
    {
        $this->validate($request, Rating::$rules);

        try {
            $session = Session::findOrFail(intval($request->input('session_id')));
            $request_id = $session->request_id;
            $user_id = $request->user()->uid;

            if (!MentorshipRequest::where('id', $request_id)->where('mentee_id', $user_id)->exists()) {
                return $this->respond(
                    Response::HTTP_FORBIDDEN,
                    ["message" => "You are not allowed to rate this session"]
                );
            }

            $session_id = $request->input('session_id');
            $values = json_encode($request->input('values'), true);
            $scale = $request->input('scale');

            $rating = Rating::create(
                [
                    "user_id" => $user_id,
                    "session_id" => $session_id,
                    "values" => $values,
                    "scale" => $scale
                ]
            );

            $response = [
                "rating" => $rating,
                "message" => "You have rated your session successfully"
            ];

            return $this->respond(Response::HTTP_CREATED, $response);

        } catch (QueryException $exception) {
            return $this->respond(
                Response::HTTP_BAD_REQUEST,
                ["message" => "You cannot rate a session twice"]
            );
        } catch (ModelNotFoundException $exception) {
            return $this->respond(
                Response::HTTP_NOT_FOUND,
                ["message" => "Session does not exist"]
            );
        }

    }
}
