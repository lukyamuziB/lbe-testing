<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    protected $table = "request_type";

    protected $fillable = ["name"];

    const MENTOR_REQUEST = 1;
    const MENTEE_REQUEST = 2;
}
