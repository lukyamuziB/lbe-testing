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
        "email"
    ];

    public static $rules = [
        "user_id" => "required|string",
        "id" => "required|string",
        "slack" => "required|boolean",
        "email" => "required|boolean"
    ];

    public function notification()
    {
        return $this->belongsTo("App\Models\Notification", "id", "id");
    }

    public function user()
    {
        return $this->belongsTo("App\Models\User", "user_id", "user_id");
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
            ->select("id", "slack", "email")
            ->orderBy("id")
            ->get();

        if (Notification::count() > count($userSettings)) {
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
                    "slack" => $notification["default"] === "slack",
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
        }
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
                "slack" => $defaultSetting["default"] === "slack",
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
        $usersSetting = UserNotification::select("user_id", "email", "slack")
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
                    "slack" => $defaultSetting["default"] === "slack"
                ];
            }
        }

        return $usersSetting;
    }
}
