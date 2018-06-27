<?php

namespace App\Http\Controllers\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use App\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\Request as Requests;
use App\Models\RequestSkill;
use App\Models\UserSkill;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;

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
     * @return array all_notifications
     */
    public function all()
    {
        $notifications = Notification::all();

        return $this->respond(Response::HTTP_OK, $notifications);
    }

    /**
     * Creates a new notification and saves it in the notifications table
     *
     * @param Request $request Request
     *
     * @throws ConflictException
     *
     * @return object Response object of created notifications
     */
    public function add(Request $request)
    {
        $this->validate($request, Notification::$rules);

        if (Notification::where('id', $request->id)->exists()) {
            throw new ConflictException("This notification already exists.");
        }
        $newNotification = Notification::create($request->all());

        return $this->respond(Response::HTTP_CREATED, $newNotification);
    }

    /**
     * Edit a notifications name field
     *
     * @param Request $request Request
     * @param string $id Unique ID of a particular notifications
     *
     * @throws NotFoundException
     *
     * @return object response of modified notifications and success message
     */
    public function put(Request $request, $id)
    {
        $this->validate($request, Notification::$update_rules);

        $notification = Notification::find($id);
        if (!$notification) {
            throw new NotFoundException("The specified notification was not found");
        }
        
        $notification->update($request->all());

        return $this->respond(Response::HTTP_OK, $notification);
    }

    /**
     * Removes a notification from notifications table
     *
     * @param string $id Unique ID used to identify the notifications
     *
     * @throws NotFoundException
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function delete($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            throw new NotFoundException("The specified notification was not found");
        }
        
        $notification->delete();

        return $this->respond(Response::HTTP_OK);
    }

    /**
     * Get all the user notification settings
     *
     * @param string $userId Unique ID used to identify the users
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSettings($userId)
    {
        $userSettings = UserNotification::getUserSettings($userId);
        return $this->respond(Response::HTTP_OK, $userSettings);
    }

    /**
     * Updates user notification settings
     *
     * @param Request $request Request
     * @param string $$userId Unique ID used to identify the users
     * @param string $notificationId Unique ID used to identify the notification types
     *
     * @throws NotFoundException
     * @throws AccessDeniedException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserSettings(Request $request, $userId, $notificationId)
    {
        $request["user_id"] = $userId;

        $this->validate($request, UserNotification::$rules);
        if (!Notification::where('id', $notificationId)->exists()) {
                throw new NotFoundException("Notification does not exist.");
        }

        $currentUser = $request->user();
        if ($currentUser->uid !== $request->user_id) {
            throw new AccessDeniedException("You do not have permission to edit this notification settings.");
        }

        $userSettings = UserNotification::updateorCreate(
            [
                "user_id" => $userId,
                "id" => $notificationId
            ],
            [
                "in_app" => $request->in_app,
                "email" => $request->email
            ]
        );
        return $this->respond(Response::HTTP_OK, $userSettings);
    }

    /**
     * Gets users that are eligible to recieve
     * notifications when a request they expressed
     * interest in is withdrawn
     *
     * @param intenger $id Unique Request Id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInterestedUsers($id)
    {
        $interestedUsersList = Requests::select("interested")
            ->where("id", $id)
            ->get();

        if (is_null($interestedUsersList[0]->interested)) {
            return [];
        }

        $userSettings = UserNotification::select("user_id", "in_app")
            ->where("id", Notification::WITHDRAWN_INTEREST)
            ->whereIn("user_id", $interestedUsersList)
            ->get();

        $usersToBeNotified = UserNotification::addDefaultSettings(
            $interestedUsersList[0]->interested,
            $userSettings->toArray(),
            "in_app"
        );

        return $this->respond(Response::HTTP_OK, $usersToBeNotified);
    }

    /**
     * Gets users that should be notified when a
     * a request with skills that match skills in
     * their profile is made
     *
     * @param intenger $id Unique Request Id
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function getUsersWithMatchingRequestSkills($id)
    {
        $requestSkills = RequestSkill::where("request_id", $id)
            ->pluck("skill_id");
      
        $usersWithThoseSkills = UserSkill::whereIn("skill_id", $requestSkills)
            ->pluck("user_id");

        $notificationEligibleUsers = UserNotification::select("user_id", "in_app")
            ->where("id", Notification::REQUESTS_MATCHING_USER_SKILLS)
            ->whereIn("user_id", $usersWithThoseSkills)
            ->get();

        $usersToBeNotified = UserNotification::addDefaultSettings(
            $usersWithThoseSkills->toArray(),
            $notificationEligibleUsers->toArray(),
            "in_app"
        );

        return $this->respond(Response::HTTP_OK, $usersToBeNotified);
    }

    /**
     * Gets users in_app settings for a
     * specified notification type
     *
     * @param Request $request Request
     * @param string $userId user's unique id
     * @param string $notificationId notification's unique id
     *
     * @throws NotFoundException
     * @throws AccessDeniedException
     *
     * @return array $userSettings user's in_app settings for
     * a given notification type
     */
    public function getUserNotificationSettings(Request $request, $userId, $notificationId)
    {
        if (!Notification::where("id", $notificationId)->exists()) {
            throw new NotFoundException("Notification does not exist.");
        }

        $currentUser = $request->user();
        if ($currentUser->uid !== $userId) {
            throw new AccessDeniedException("You do not have permission to access this notification settings.");
        }

        $userSettings = UserNotification::select("user_id", "id", "in_app")
            ->where("id", $notificationId)
            ->where("user_id", $userId)
            ->get();

        if (empty($userSettings->toArray())) {
            $userSettings = [[
                "user_id" => $userId,
                "id" => $notificationId,
                "in_app" => true
            ]];
        }

        return $this->respond(Response::HTTP_OK, $userSettings);
    }
}
