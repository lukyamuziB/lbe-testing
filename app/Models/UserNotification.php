<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserNotification extends Model {
    
    protected $table = 'user_notifications';
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
        return $this->belongsTo('App\Models\Notification', 'id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'user_id');
    }

    /**
     * Get all a user's notification settings
     * if no settings exist, it returns the defaults of all notifications
     *
     * @param string $user_id user's unique id
     *
     * @return array $user_settings all notification settings for user
     */
    public static function getUserSettings($user_id)
    {
        $user_settings = UserNotification::where('user_id', $user_id)
        ->select('id', 'slack', 'email')
        ->get();

        if (Notification::count() > count($user_settings)) {
            $notifications = Notification::whereNotIn(
                'id', $user_settings->pluck("id")
            )->select('id', 'default')
                ->get();

            $default_settings = [];
            foreach ($notifications as $notification) {
                $default_settings[] = [
                    "id" => $notification["id"],
                    "email" => $notification["default"] === "email",
                    "slack" => $notification["default"] === "slack",
                ];
            }
            
            $user_settings = array_merge(
                $default_settings, $user_settings->toArray()
            );
        }
        return $user_settings;
    }

    /**
     * Get user notification setting by user_id and notification id
     *
     * @param integer $user_id         Unique ID used to identify the users
     * @param integer $notification_id Unique ID used to identify the notification
     *
     * @return UserNotification $user_setting a user's setting for notification
     */
    public static function getUserSettingById($user_id, $notification_id)
    {
        $user_setting = UserNotification::where("user_id", $user_id)
            ->where("id", $notification_id)
            ->first();

        if (!$user_setting) {
            $default_setting = Notification::select("default")
                ->where("id", $notification_id)
                ->first();

            $user_setting = [
                "notification_id" => $notification_id,
                "slack" => $default_setting["default"] === "slack",
                "email" => $default_setting["default"] === "email"
            ];
        }

        return $user_setting;
    }

}
