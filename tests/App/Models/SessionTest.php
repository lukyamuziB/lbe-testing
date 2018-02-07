<?php

namespace Tests\App\Models;

use TestCase;
use App\Models\Session;

class SessionTest extends TestCase
{
    /**
     * Test session is returned when find by date.
     *
     * @return void
     */
    public function testFindSessionByDateSuccess()
    {
        $loggedSessions = Session::with('files')->where("request_id", 10)
            ->get(["date", "mentee_approved", "mentor_approved"]);
        $date = $loggedSessions[0]->date;

        $session = Session::findSessionByDate($loggedSessions, $date);
        $this->assertEquals($date, $session->date);
    }
}
