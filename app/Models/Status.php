<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Status extends Model
{
    protected $table = 'status';

    protected $fillable = ['name'];

    const OPEN = 1;
    const MATCHED = 2;
    const COMPLETED = 3;
    const CANCELLED = 4;
}
