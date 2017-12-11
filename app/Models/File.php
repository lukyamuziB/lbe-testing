<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = "files";

    protected $fillable = [
        "name",
    ];

    public static $rules = [
        "name" => "required|string",
        "generated_name" => "required",
    ];

    /**
     * Defines Foreign Key Relationship to the session model
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function sessions()
    {
        return $this->belongsToMany("App\Models\Session", "session_file", "file_id", "session_id");
    }
}
