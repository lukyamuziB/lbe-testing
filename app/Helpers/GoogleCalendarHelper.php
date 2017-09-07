<?php

use App\Exceptions\NotFoundException;

/**
 * Convert Timezone format
 *
 * @param string $timezone_key short form key representing timezone
 *
 * @return string $timezone along form timezone format
 * @throws Exception
 * @throws NotFoundException
 */
function formatCalendarTimezone($timezone_key)
{
    $timezones = ["EAT" => "Africa/Nairobi", "WAT" => "Africa/Lagos"];
    
    if (!in_array($timezone_key, array_keys($timezones))) {
        throw new NotFoundException("Timezone not found");
    }
    
    return $timezones[$timezone_key];
}

/**
 * Set the recurring rule for selected days
 *
 * @param array  $session_days session days as per user choice [monday,tuesday,...]
 * @param string $end_date     last date for this event
 *
 * @return string formatted recursion Rule
 * @throws InvalidArgumentException
 */
function getCalendarRecursionRule($session_days, $end_date)
{
    if (!is_array($session_days)) {
        throw new InvalidArgumentException("Days must be passed as an array");
    }
    
    if (count($session_days) == 0) {
        throw new InvalidArgumentException("Missing days values");
    }
    
    if ($end_date === "") {
        throw new InvalidArgumentException("Missing date value");
    }
    
    $until = preg_replace("/:|-/", "", $end_date) . "Z";
    $week_days = [
        "monday" => "MO",
        "tuesday" => "TU",
        "wednesday" => "WE",
        "thursday" => "TH",
        "friday" => "FR",
        "saturday" => "SA",
        "sunday" => "SU"
    ];
    
    $days = array_intersect_key($week_days, array_flip($session_days));
    $days = implode(",", array_values($days));
    
    return "RRULE:FREQ=WEEKLY;BYDAY=$days;UNTIL=$until";
}

/**
 * Calculate and event start date
 *
 * @param array  $session_days session days as per user choice [monday,tuesday,...]
 * @param string $date         sast date for this event
 *
 * @return string $date_time
 * @throws InvalidArgumentException
 */
function calculateEventStartDate($session_days, $date = "")
{
    if (!is_array($session_days)) {
        throw new InvalidArgumentException("Days must be passed as an array");
    }
    
    if (count($session_days) == 0) {
        throw new InvalidArgumentException("Missing days values");
    }
    
    if ($date === "") {
        throw new InvalidArgumentException("Missing date value");
    }
    
    $date_time = date("Y-m-d", strtotime($date));
    $date_day = date("l", strtotime($date_time));
    
    while (!in_array(strtolower($date_day), $session_days)) {
        $date_time = date("Y-m-d", strtotime("+1 days", strtotime($date_time)));
        $date_day = date("l", strtotime($date_time));
    }
    
    return $date_time;
}

/**
 * Format calendar date
 *
 * @param string $date     date to format
 * @param string $time     time to append at the date string
 * @param int    $duration time interval in months
 *
 * @return string formatted calendar date
 * @throws InvalidArgumentException
 */
function formatCalendarDate($date = "", $time = "", $duration = 0)
{
    //Date in YYYY-mm-dd
    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)
    ) {
        throw new InvalidArgumentException("Invalid date format");
    }
    
    //Time in H:m:s
    if (!preg_match("/^(?:([01]?\d|2[0-3]):([0-5]?\d):)?([0-5]?\d)$/", $time)) {
        throw new InvalidArgumentException("Invalid time format");
    }
    
    if (!is_numeric($duration)) {
        throw new InvalidArgumentException("Invalid duration value");
    }
    
    if ($duration != 0) {
        $date = date(
            "Y-m-d",
            strtotime("+$duration months", strtotime($date))
        );
    }
    
    return $date . "T" . $time;
}
