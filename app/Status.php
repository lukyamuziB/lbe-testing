<?php
namespace App;

use Illuminate\Database\Eloquent\Model;


class Status extends Model
{
    protected $table = 'status';

    protected $fillable = ['name'];

    const OPEN = 1;
    const MATCHED = 2;
    const CLOSED = 3;
    const CANCELLED = 4;
}
