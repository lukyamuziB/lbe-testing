<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        "id",
        "default",
        "description"
    ];

    public static $rules = [
        "id" => "required|string|regex:/^([A-Z_]*$)/",
        "default" => "required|string",
        "description" => "string"
    ];
    public static $update_rules = [
        "id" => "string|regex:/^([A-Z_]*$)/",
        "default" => "required|string",
        "description" => "string"
    ];
}
