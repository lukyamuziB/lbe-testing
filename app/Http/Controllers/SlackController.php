<?php

namespace App\Http\Controllers;

use App\User as User;
use App\Exceptions\Exception;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\NotFoundException;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Lcobucci\JWT\Parser;
use App\Utility\SlackUtility as Slack;

class SlackController extends Controller
{
    use RESTActions;

    /**
     * updateUserId gets a users slack handle and updates the users' slack handle.
     *
     * @param object $request the request object
     * @param object $slack_provider the slack provider service
     * @return object the updated user details
     */
    public function updateUserId(Request $request, Slack $slack_provider)
    {
        $this->validate($request, User::$slack_update_rules);

        try {
            $current_user = $request->user();

            if (!$current_user) {
                throw new AccessDeniedException('You are not authorized to perform this action.', 1);
            }

            // Retrieve the users' slack id using the handle supplied in the request and save it.
            $message = 'You\'ve just updated your Slack id on Lenken ' . $request->slack_handle;
            $slack_response = $slack_provider->sendMessage($request->slack_handle, $message);

            preg_match('/@\w+/', $slack_response["message"]["text"], $matches);

            $user_slack_id = substr($matches[0], 1);
            $user_details = [
                "user_id" => $current_user->uid,
                "email" => $current_user->email
            ];
            $user = User::updateOrCreate($user_details, ["slack_id" => $user_slack_id]);

        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        $response = [
            "data" => $user
        ];

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * SendMessage sends a slack message to a user.
     *
     * @param object $request the request object
     * @param object $slack_provider the slack provider service
     * @return object the slack response object
     */
    public function sendMessage(Request $request, Slack $slack_provider)
    {
        $this->validate($request, User::$slack_send_rules);

        try {
            $current_user = $request->user();

            if (!$current_user) {
                throw new AccessDeniedException("you don't have permission to send slack messages", 1);
            }

            $slack_response = $slack_provider->sendMessage($request->channel, $request->text);
        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        return $this->respond(Response::HTTP_OK, $slack_response);
    }
}
