<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use App\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Notification;
use App\Models\UserNotification;

/**
 * Class NotificationController
 *
 * @package App\Http\Controllers
 */
class NotificationController extends Controller
{
    use RESTActions;

    /**
     *  Fetch all notifications
     *
     * @return Response object - all notifications object
     */
    public function all()
    { 
        $notifications = Notification::all();

        return $this->respond(Response::HTTP_OK, $notifications);
    }

    /**
     * Creates a new notification and saves it in the notifications table
     *
     * @param object $request Request
     *
     * @return object Response object of created notifications
     */
    public function add(Request $request)
    {
        $this->validate($request, Notification::$rules);
        if (Notification::where('id', $request->id)->exists()) {
            return $this->respond(
                Response::HTTP_CONFLICT,
                ["message" => "Notification already exists"]
            );
        }
        $new_notification = Notification::create($request->all());

        return $this->respond(Response::HTTP_CREATED, $new_notification);

    }

    /**
     * Edit a notifications name field
     *
     * @param object   $request Request
     * @param sinteger $id      Unique ID of a particular notifications
     *
     * @return object response of modified notifications and success message
     */
    public function put(Request $request, $id)
    {
        $this->validate($request, Notification::$update_rules);
        if (!Notification::where('id', $id)->exists()) {
            throw new NotFoundException("The specified notification was not found");
        }
        $notification = Notification::find($id);
        $notification->update($request->all());

        return $this->respond(Response::HTTP_OK,  $notification);
    }

    /**
     * Removes a notifications from notifications table
     *
     * @param object  $request Request
     * @param integer $id      Unique ID used to identify the notifications
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        if (!Notification::where('id', $id)->exists()) {
            throw new NotFoundException("The specified notification was not found");
        }
        $notification = Notification::find($id);
        $notification->delete();

        return $this->respond(Response::HTTP_OK, $notification);
    }

    /**
     * Get a notifications from notifications table
     *
     * @param integer $user_id Unique ID used to identify the users
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotificationsByUserId($user_id)
    {
        $user_settings = UserNotification::getUserSettings($user_id);
        return $this->respond(Response::HTTP_OK, $user_settings);
    }
    
    /**
     * Updates user notificstion settings
     *
     * @param object  $request         Request
     * @param integer $user_id         Unique ID used to identify the users
     * @param integer $notification_id Unique ID used to identify the notifications
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserSettings(Request $request, $user_id, $notification_id)
    {
        $this->validate($request, UserNotification::$rules);
        if (!Notification::where('id', $notification_id)->exists()) {
            return $this->respond(
                Response::HTTP_BAD_REQUEST,
                ["message" => "Notification does not exist"]
            );
        }
            $user_settings = UserNotification::updateorCreate(
                [
                    "user_id" => $user_id,
                    "id" => $notification_id
                ], 
                [
                    "slack" => $request->slack,
                    "email" => $request->email
                ]
            );
            return $this->respond(Response::HTTP_OK, $user_settings);
    }
}
