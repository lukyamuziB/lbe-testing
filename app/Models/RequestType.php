<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    protected $table = "request_type";

    protected $fillable = ["name"];

    const SEEKING_MENTEE = 1;
    const SEEKING_MENTOR = 2;
}
