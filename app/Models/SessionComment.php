<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionComment extends Model
{
    protected $fillable = [
        "session_id",
        "user_id",
        "comment"
    ];

    /**
     * Defines Foreign Key Relationship to the Session model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function session()
    {
        return $this->belongsTo("App\Models\Session");
    }

    /**
     * Defines Foreign Key Relationship to the User model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo("App\Models\User", "user_id", "id");
    }
}
