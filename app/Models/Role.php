<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = "role";

    protected $fillable = ["name"];

    const MENTOR = 1;
    const MENTEE = 2;
}
