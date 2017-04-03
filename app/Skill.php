<?php
namespace App;

use Illuminate\Database\Eloquent\Model;


class Skill extends Model
{
    protected $fillable = ['name'];

    /**
     * Lookup a skill that matches the argument
     *
     * @param  string $name Name of the skill
     */
    public static function findMatching($name)
    {
        return self::where('name', 'iLIKE', "%$name%")->get();
    }
}
