<?php

namespace App\Http\Controllers;

use App\Exceptions\UnauthorizedException;
use App\User as User;
use App\Exceptions\Exception;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\NotFoundException;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Utility\SlackUtility;

class SlackController extends Controller
{
    use RESTActions;

    /**
     * updateUserId gets a users slack handle and updates the users' slack handle.
     *
     * @param object $request the request object
     * @param object $slack_utility the slack provider service
     * @return object the updated user details
     */
    public function updateUserId(Request $request, SlackUtility $slack_utility)
    {
        $this->validate($request, User::$slack_update_rules);

        try {
            $current_user = $request->user();

            if (!$current_user) {
                throw new AccessDeniedException(
                    'You are not authorized to perform this action.', 1
                );
            }

            // Verify the user's slack handle
            $slack_user = $slack_utility->verifyUserSlackHandle(
                $request->slack_handle, $current_user->email
            );

            $user_details = [
                "user_id" => $current_user->uid,
                "email" => $current_user->email,
                "slack_id" => $slack_user->id
            ];

            $user = User::updateOrCreate(
                ["user_id" => $current_user->uid], $user_details
            );

            $message = "You've just updated your Slack id on Lenken $request->slack_handle";

            $slack_utility->sendMessage($request->slack_handle, $message);

        } catch (NotFoundException $exception) {
            return $this->respond(
                Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]
            );
        } catch (UnauthorizedException $exception) {
            return $this->respond(
                Response::HTTP_UNAUTHORIZED, ["message" => $exception->getMessage()]
            );
        } catch (Exception $exception) {
            return $this->respond(
                Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]
            );
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
     * @param object $slack_utility the slack provider service
     * @return object the slack response object
     */
    public function sendMessage(Request $request, SlackUtility $slack_utility)
    {
        $this->validate($request, User::$slack_send_rules);

        try {
            $current_user = $request->user();

            if (!$current_user) {
                throw new AccessDeniedException("you don't have permission to send slack messages", 1);
            }

            $slack_response = $slack_utility->sendMessage($request->channel, $request->text);
        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        return $this->respond(Response::HTTP_OK, $slack_response);
    }
}
