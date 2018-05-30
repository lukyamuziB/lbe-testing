<?php

use App\Exceptions\NotFoundException;

/**
 * Set the recurring rule for selected days
 *
 * @param array  $sessionDays session days as per user choice [monday,tuesday,...]
 * @param string $endDate     last date for this event
 *
 * @return string formatted recursion Rule
 * @throws InvalidArgumentException
 */
function getCalendarRecursionRule($sessionDays, $endDate)
{
    if (!is_array($sessionDays)) {
        throw new InvalidArgumentException("Days must be passed as an array");
    }

    if (count($sessionDays) == 0) {
        throw new InvalidArgumentException("Missing days values");
    }

    if ($endDate === "") {
        throw new InvalidArgumentException("Missing date value");
    }

    $until = preg_replace("/:|-/", "", $endDate) . "Z";
    $weekDays = [
        "monday" => "MO",
        "tuesday" => "TU",
        "wednesday" => "WE",
        "thursday" => "TH",
        "friday" => "FR",
        "saturday" => "SA",
        "sunday" => "SU"
    ];

    $days = array_intersect_key($weekDays, array_flip($sessionDays));
    $days = implode(",", array_values($days));

    return "RRULE:FREQ=WEEKLY;BYDAY=$days;UNTIL=$until";
}

/**
 * Calculate and event start date
 *
 * @param array  $sessionDays session days as per user choice [monday,tuesday,...]
 * @param string $date         sast date for this event
 *
 * @return string $dateTime
 * @throws InvalidArgumentException
 */
function calculateEventStartDate($sessionDays, $date = "")
{
    if (!is_array($sessionDays)) {
        throw new InvalidArgumentException("Days must be passed as an array");
    }

    if (count($sessionDays) == 0) {
        throw new InvalidArgumentException("Missing days values");
    }

    if ($date === "") {
        throw new InvalidArgumentException("Missing date value");
    }

    $dateTime = date("Y-m-d", strtotime($date));
    $dateDay = date("l", strtotime($dateTime));

    while (!in_array(strtolower($dateDay), $sessionDays)) {
        $dateTime = date("Y-m-d", strtotime("+1 days", strtotime($dateTime)));
        $dateDay = date("l", strtotime($dateTime));
    }

    return $dateTime;
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

/**
 * Format timezone to suit google calendar API
 *
 * @param string $timezone timezone to format
 *
 * @return string $timezone  formatted calendar date
 */
function formatTimezone($timezone = "Africa/Lagos")
{
    if ($timezone === "WAT") {
        $timezone = "Africa/Lagos";
    } elseif ($timezone === "EAT") {
        $timezone = "Africa/Nairobi";
    }
    return $timezone;
}

/**
 * Format event details for google calendar
 *
 * @param object $mentorshipRequest mentorship request made
 *
 * @return array $formattedEventDetails formatted Event details for google calendar
 */
function formatEventDetailsForGoogleCalendar($mentorshipRequest)
{
    $formattedEventDetails["timezone"] = formatTimezone($mentorshipRequest->pairing["timezone"]);

    $formattedEventDetails["startDate"] = calculateEventStartDate(
        $mentorshipRequest->pairing["days"],
        $mentorshipRequest->match_date
    );

    $formattedEventDetails["startTime"] = formatCalendarDate(
        $formattedEventDetails["startDate"],
        $mentorshipRequest->pairing["start_time"] . ":00"
    );

    $formattedEventDetails["endTime"] = formatCalendarDate(
        $formattedEventDetails["startDate"],
        $mentorshipRequest->pairing["end_time"] . ":00"
    );

    $formattedEventDetails["endDate"] = formatCalendarDate(
        $formattedEventDetails["startDate"],
        $mentorshipRequest->pairing["end_time"] . ":00",
        $mentorshipRequest->duration
    );

    $formattedEventDetails["recursionRule"] = getCalendarRecursionRule(
        $mentorshipRequest->pairing["days"],
        $formattedEventDetails["endDate"]
    );

    return $formattedEventDetails;
}

/**
 * Get event details for google calendar
 *
 * @param object $mentorshipRequest mentorship request made
 *
 * @return array $eventDetails formatted Event details for google calendar
 */
function getEventDetails($mentorshipRequest)
{
    $formattedEventDetails = formatEventDetailsForGoogleCalendar($mentorshipRequest);

    $mentorName = explode(" ", $mentorshipRequest->mentor->fullname);
    $menteeName = explode(" ", $mentorshipRequest->mentee->fullname);

    $eventDetails = [
        "summary" => $mentorName[0]."<>".$menteeName[0],
        "description" => $mentorshipRequest->description,
        "start" => [
            "dateTime" => $formattedEventDetails["startTime"],
            "timeZone" => $formattedEventDetails["timezone"]
        ],
        "end" => [
            "dateTime" => $formattedEventDetails["endTime"],
            "timeZone" => $formattedEventDetails["timezone"]
        ],
        "recurrence" => [$formattedEventDetails["recursionRule"]],
        "attendees" => [
            ["email" => $mentorshipRequest->mentor->email],
            ["email" => $mentorshipRequest->mentee->email],
        ],
        "reminders" => [
            "useDefault" => false,
            "overrides" => [
                ["method" => "email", "minutes" => 24 * 60],
                ["method" => "popup", "minutes" => 10],
            ],
        ]
    ];
    return $eventDetails;
}

/**
 * Schedule pairing session on calendar
 *
 * @param GoogleCalendarClient $googleCalendar google client
 * @param object $mentorshipRequest mentorship request made
 *
 * @return void
 */
function schedulePairingSessionsOnCalendar(
    $googleCalendar,
    $mentorshipRequest
) {
        $eventDetails = getEventDetails($mentorshipRequest);
        $googleCalendar->createEvent($eventDetails);
}
