<?php
namespace App;

use Illuminate\Database\Eloquent\Model;


class Status extends Model
{
    protected $table = 'status';

    protected $fillable = ['name'];

    const OPEN = 0;
    const MATCHED = 1;
    const CLOSED = 2;
    const CANCELLED = 3;
}
