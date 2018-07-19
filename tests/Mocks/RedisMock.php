<?php

namespace Test\Mocks;

/**
 * Class RedisMock imitates a typical redis cache
 *
 * @package Test\Mocks
 */
class RedisMock
{
    static protected $model = [];

    /**
     * RedisMock constructor.
     */
    public function __construct()
    {
        self::$model = [
            "users:L4g35ttuyfK5kpzyocv:lastActive" => "2018-04-08 18:02:48",
            "users:-K_nkl19N6-EGNa0W8LF:lastActive" => "2018-02-08 18:02:48",
            "users:-KXGy1MT1oimjQgFim7u:lastActive" => "2018-03-08 18:02:48",
            "users:-KesEogCwjq6lkOzKmLI:lastActive" => "2018-03-08 18:02:48",
            "users:-KXGy1MimjQgFim7u:lastActive" => "2018-03-08 18:02:48",
        ];
    }

    /**
     * Sets/Adds a key - value to the model
     *
     * @param String $key - the data key
     * @param String $value - the data value
     *
     * @return Void
     */
    public static function set($key, $value)
    {
        self::$model[] = [$key => $value];
    }

    /**
     * Gets value found for the supplied key
     *
     * @param String $key - the data key
     *
     * @return String string
     */
    public static function get($key)
    {
        return self::$model[$key];
    }

    /**
     * Gets array of values found for the supplied keys
     *
     * @param Array $keys - the data keys
     *
     * @return Array $dataFound - array of values gotten from key
     */
    public static function mget($keys)
    {
        $dataFound = [];
        foreach ($keys as $key) {
            if (in_array($key, array_keys(self::$model))) {
                $dataFound[] = self::$model[$key];
            }
        }

        return $dataFound;
    }
}
