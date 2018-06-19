<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
                "email" => $notification["default"] === "email",
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
}
