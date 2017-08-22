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

    public static function getUserSettings($user_id) {
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


}
