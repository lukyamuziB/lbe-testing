<?php

namespace App\Clients;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

/**
 * Class GoogleCalendarClient makes API calls to Google API
 *
 * @package APP\Clients
 */

class GoogleCalendarClient
{
    protected $service;

    /**
     * GoogleCalendarClient constructor
     */
    public function __construct()
    {
        $api_key = getenv('GOOGLE_API_KEY');
        
        $client = new Google_Client();
        $client->setAccessType("offline");
        $client->useApplicationDefaultCredentials();
        $client->setDeveloperKey($api_key);
        $client->setSubject(getenv('GOOGLE_SERVICE_ACCOUNT_NAME'));
        $client->setScopes(['https://www.googleapis.com/auth/calendar']);
        
        $this->service = new Google_Service_Calendar($client);
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
        $event = new Google_Service_Calendar_Event($event_details);

        $optional_arguments = ["sendNotifications" => true];
        $calendar_id = 'primary';
        $event = $this->service->events
            ->insert($calendar_id, $event, $optional_arguments);

        return $event;
    }
}
