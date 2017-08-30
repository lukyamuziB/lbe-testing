<?php

namespace Test\Mocks;

use App\Clients\GoogleCalendarClient;
use Google\Auth\ApplicationDefaultCredentials;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

/**
 * Class GoogleCalendarClient makes API calls to Google API
 *
 * @package APP\Clients
 */

class GoogleCalendarClientMock extends GoogleCalendarClient
{

    public $called;
    /**
     * GoogleCalendarClient constructor
     */
    public function __construct()
    {
        $this->called = false;
    }

    /**
     * Creates an event
     *
     * @param array $event_details event details e.g summary, start, end, attendees, e.t.c
     *
     * @return array $user of a user
     */
    public function createEvent($event_details)
    {
        $this->called = true;
        return $event_details;
    }
}
