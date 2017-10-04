<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RequestExtension
 *
 * @package App\Models
 */
class RequestExtension extends Model
{

    protected $table = "request_extensions";

    protected $fillable = [
        "request_id",
        "approved",
        "created_at"
    ];

    public static $rules = [
        "request_id" => "numeric",
        "approved" => "boolean"
    ];

    /**
     * Foreign key relationship definition
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function request()
    {
        return $this->belongsTo("App\Models\Request", "request_id");
    }
}
