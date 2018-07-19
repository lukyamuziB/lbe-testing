<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\NotFoundException;
use App\Models\RequestSkill;
use App\Models\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;

class UserNotification extends Model
{

    protected $table = "user_notifications";
    public $incrementing = false;
    protected $fillable = [
        "user_id",
        "id",
        "slack",
        "email",
        "in_app"
    ];

    const ACCEPT_USER = "accept_user";
    const REJECT_USER = "reject_user";

    public static $rules = [
        "user_id" => "required|string",
        "id" => "required|string",
        "email" => "required|boolean",
        "in_app" => "required|boolean"
    ];

    public function notification()
    {
        return $this->belongsTo("App\Models\Notification", "id", "id");
    }

    public function user()
    {
        return $this->belongsTo("App\Models\User", "user_id", "id");
    }

    /**
     * Get all a user's notification settings
     * if no settings exist, it returns the defaults of all notifications
     *
     * @param string $userId user's unique id
     *
     * @return array $userSettings all notification settings for user
     */
    public static function getUserSettings($userId)
    {
        $userSettings = UserNotification::with(
            ['notification' => function ($query) {
                $query->select('id', 'description');
            }]
        )
        ->where("user_id", $userId)
        ->select("id", "in_app", "email")
        ->orderBy("id")
        ->get();

        $notifications = Notification::whereNotIn(
            "id",
            $userSettings->pluck("id")
        )->select("id", "default", "description")
            ->orderBy("id")
            ->get();
        $defaultSettings = [];
        foreach ($notifications as $notification) {
            $defaultSettings[] = [
                "id" => $notification["id"],
                "description" => $notification["description"],
                "email" => true,
                "in_app" => $notification["default"] === "in_app",
            ];
        }

        $userSettings = array_map(
            function ($setting) {
                $setting["description"] = $setting["notification"]["description"];
                unset($setting["notification"]);
                return $setting;
            },
            $userSettings->toArray()
        );
        $allUserSettings = array_merge(
            $defaultSettings,
            $userSettings
        );
        return $allUserSettings;
    }

    /**
     * factor in default settings when
     * checking for a user's eligibility to
     * receive notifications
     *
     * @param array $notificationPotentialUsers - all users associated with a given request
     * @param array $customNotificationSettings - users who have their settings in the user_notifications table
     * @param string $type - Notification type, which can either be in_app or email
     */
    public static function addDefaultSettings($potentialNotficationRecipients, $customNotificationSettings, $type)
    {
        $usersToBeNotified = [];
        $usersWithCustomSettings = [];
        
        foreach ($customNotificationSettings as $setting) {
            array_push($usersWithCustomSettings, $setting["user_id"]);
            if ($setting[$type] === true) {
                array_push($usersToBeNotified, $setting["user_id"]);
            }
        }

        $usersWithoutCustomSettings = array_diff($potentialNotficationRecipients, $usersWithCustomSettings);

        $usersEligibleForNotification = array_merge($usersToBeNotified, $usersWithoutCustomSettings);

        return $usersEligibleForNotification;
    }

    /**
     * Get user notification setting by user_id and notification id
     *
     * @param integer $userId Unique ID used to identify the users
     * @param integer $notificationId Unique ID used to identify the notification
     *
     * @return UserNotification $userSetting a user's setting for notification
     */
    public static function getUserSettingById($userId, $notificationId)
    {
        $userSetting = UserNotification::where("user_id", $userId)
            ->where("id", $notificationId)
            ->first();

        if (!$userSetting) {
            $defaultSetting = Notification::select("default")
                ->where("id", $notificationId)
                ->first();

            $userSetting = (object)[
                "notification_id" => $notificationId,
                "in_app" => $defaultSetting["default"] === "in_app",
                "email" => $defaultSetting["default"] === "email"
            ];
        }

        return $userSetting;
    }

    /**
     * Get notification settings for multiple users
     *
     * @param array $userIds user IDs
     * @param string $notificationId the notification ID
     *
     * @return array
     */
    public static function getUsersSettingById($userIds, $notificationId)
    {
        $usersSetting = UserNotification::select("user_id", "email", "in_app")
            ->whereIn("user_id", $userIds)
            ->where("id", $notificationId)
            ->get()
            ->toArray();

        $fetchedIds = array_column($usersSetting, "user_id");
        $unfetchedIds = array_diff($userIds, $fetchedIds);

        if ($unfetchedIds) {
            $defaultSetting = Notification::select("default")
                ->where("id", $notificationId)
                ->first();

            foreach ($unfetchedIds as $userId) {
                $usersSetting[] = [
                    "user_id" => $userId,
                    "email" => $defaultSetting["default"] === "email",
                    "in_app" => $defaultSetting["default"] === "in_app"
                ];
            }
        }

        return $usersSetting;
    }

    /**
     * Gets users email settings for a
     * specified notification type
     *
     * @param string $userEmail user's unique email
     * @param string $notificationId notification's unique id
     *
     * @throws NotFoundException
     *
     * @return boolean $userSettings user's email settings for
     * a given notification type
     */
    public static function hasUserAcceptedEmail($userEmail, $notificationId)
    {
        if (!Notification::where("id", $notificationId)->exists()) {
            throw new NotFoundException("Notification Not Found Exception.");
        }

        $userId = User::where("email", $userEmail)
            ->pluck("id");
 
        $userSettings = UserNotification::where("id", $notificationId)
            ->where("user_id", $userId)
            ->pluck("email");

        if (empty($userSettings->toArray())) {
            $userSettings = "true";
        }

        return $userSettings;
    }

    /**
     * Gets users that should be notified when a
     * a request with skills that match skills in
     * their profile is made
     *
     * @param intenger $id Unique Request Id
     *
     * @return array $notificationEligibleUsers
     *
     */
    public static function getUsersWithMatchingRequestSkills($id)
    {
        $requestSkills = RequestSkill::where("request_id", $id)
            ->pluck("skill_id");
        
        $usersWithThoseSkills = UserSkill::whereIn("skill_id", $requestSkills)
            ->pluck("user_id");

        $notificationEligibleUsers = UserNotification::select("user_id", "email")
            ->where("id", Notification::REQUESTS_MATCHING_USER_SKILLS)
            ->whereIn("user_id", $usersWithThoseSkills)
            ->get();
        
        $usersToBeNotified = UserNotification::addDefaultSettings(
            $usersWithThoseSkills->toArray(),
            $notificationEligibleUsers->toArray(),
            "email"
        );
        
        return $usersToBeNotified;
    }

    /**
     * Gets users that are eligible to receive email
     * notifications when a request they expressed
     * interest in is withdrawn
     *
     * @param intenger $id Unique Request Id
     *
     * @return array $usersToBeNotified
     */
    public static function getInterestedUsers($id)
    {
        $interestedUsersList = Request::select("interested")
            ->where("id", $id)
            ->get();
        
        if (is_null($interestedUsersList[0]->interested)) {
            return [];
        }

        $notificationEligibleUsers = UserNotification::select("user_id", "email")
            ->where("id", Notification::WITHDRAWN_INTEREST)
            ->whereIn("user_id", $interestedUsersList[0]->interested)
            ->get();
    
        $usersToBeNotified = UserNotification::addDefaultSettings(
            $interestedUsersList[0]->interested,
            $notificationEligibleUsers->toArray(),
            "email"
        );
        
        return $usersToBeNotified;
    }

    /**
     * Gets users that are eligible to receieve email
     * notifications when a request with skills
     *  they have an opening for is made
     *
     * @param intenger $id Unique Request Id
     *
     * @return array $usersToBeNotified
     */
    public static function getUsersWithMatchingOpenSkills($id)
    {
        $requestSkills = RequestSkill::where("request_id", $id)
            ->pluck("skill_id");
        
        $mentorsMatchingSkills = Request::select('created_by')
            ->where("request_type_id", ROLE::MENTEE)
            ->where("status_id", STATUS::OPEN)
            ->where("id", "<>", $id)
            ->whereIn("id", RequestSkill::whereIn("skill_id", $requestSkills)
                ->pluck("request_id")
                ->toArray())
            ->get();

        $mentorIds = [];
        foreach ($mentorsMatchingSkills as $mentor) {
            array_push($mentorIds, $mentor->created_by->id);
        }

        $notificationEligibleUsers = UserNotification::select("user_id", "email")
            ->where("id", Notification::MATCHING_OPEN_REQUEST_SKILLS)
            ->whereIn("user_id", $mentorIds)
            ->get();
        
        $usersToBeNotified = UserNotification::addDefaultSettings(
            $mentorIds,
            $notificationEligibleUsers->toArray(),
            "email"
        );
        
        return $usersToBeNotified;
    }
}
