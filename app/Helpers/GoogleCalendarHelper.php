<?php

/**
 * Convert Timezone format
 *
 * @param string $timezone_key Shortform key representing timezone
 *
 * @return string $timezone Along form Timezone format
 */
function formatCalendarTimezone($timezone_key)
{
    $timezones = ["EAT" => "Africa/Nairobi", "WAT" => "Africa/Lagos"];
    return $timezones[$timezone_key];
}

/**
 * Set the recurring rule for selected days
 *
 * @param array $session_days Session days as per user choice [monday,tuesday,...]
 * @param string $end_date Last date for this event
 *
 * @return string Formated Recursion Rule
 */
function getCalendarRecursionRule($session_days, $end_date)
{
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
 * @param array $session_days Session days as per user choice [monday,tuesday,...]
 * @param string $date Last date for this event
 *
 * @return string $date_time
 */
function calculateEventStartDate($session_days, $date)
{
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
 * @param string $date Date to format
 * @param string $time Time to append at the date string
 * @param string $duration Time interval in months
 *
 * @return string Formated calendar date
 */
function formatCalendarDate($date, $time, $duration = 0)
{
    $date = date("Y-m-d", strtotime($date));

    if ($duration != 0) {
        $date = date(
            "Y-m-d",
            strtotime("+$duration months", strtotime($date))
        );
    }
    return $date . "T" . $time;
}
