<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    protected $table = "request_type";

    protected $fillable = ["name"];

    const MENTEE_REQUEST = 1;
    const MENTOR_REQUEST = 2;
}
