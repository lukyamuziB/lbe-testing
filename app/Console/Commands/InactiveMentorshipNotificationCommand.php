<?php

namespace App\Console\Commands;

use App\Models\Request;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\InactiveMentorshipNotificationMail;

/**
 * Class InactiveMentorshipNotificationCommand
 *
 * @package App\Console\Commands
 */
class InactiveMentorshipNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:inactive-mentorships';
    protected $description = 'Sends an email notification when 
    more than 3 sessions are not logged';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $allMatchedRequests = Request::with('sessions', 'mentee', 'mentor')
            ->where('status_id', Status::MATCHED)->get();

        $inactiveRequests = $this->getInactiveRequests($allMatchedRequests);
        if (!$inactiveRequests) {
            return $this->info("There are no inactive mentorships");
        }

        $inactiveMentorshipEmails = $this->getEmailsFromRequests($inactiveRequests);
        $numberOfFellows = count($inactiveMentorshipEmails);

        Mail::to($inactiveMentorshipEmails)->send(
            new InactiveMentorshipNotificationMail()
        );

        return $this->info("Inactive notifications have been sent to $numberOfFellows fellows");
    }

    /**
     * Get the emails of fellows from inactive requests
     *
     * @param array $requests - array of requests
     *
     * @return array - array of all emails
     */
    private function getEmailsFromRequests($requests)
    {
        $emails = [];
        foreach ($requests as $request) {
            $emails[] = $request["mentee"]["email"];
            $emails[] = $request["mentor"]["email"];
        }

        return array_unique($emails);
    }

    /**
     * Get all inactive requests
     *
     * @param array $requests - array of requests & its sessions
     *
     * @return array $inactiveRequests - holds all the inactive requests
     */
    private function getInactiveRequests($requests)
    {
        $inactiveRequests = [];

        foreach ($requests as $request) {
            if ($this->isRequestInactive($request)) {
                $request = array_except($request, ['sessions']);
                $inactiveRequests[] = $request;
            }
        }

        return $inactiveRequests;
    }

    /**
     * Check whether a single request is inactive. A request is
     * inactive if there's no logged session out of the last three ideal scheduled
     * sessions dates.
     *
     * @param array $request - single request object with its sessions
     *
     * @return boolean - whether request is inactive or not
     */
    private function isRequestInactive($request)
    {
        $matchDate = date('Y-m-d H:i:s', strtotime($request['match_date']));
        $pairingDays = $request['pairing']['days'];

        // date when the first of the last three sessions was supposed to happen
        $expectedAntepenultimateSessionDate = $this->getAntepenultimateSessionDate($pairingDays);

        //a request needs to be of a lengthy period, enough to have had 3 scheduled sessions from match date
        if ($expectedAntepenultimateSessionDate > $matchDate) {
            // check for logged sessions within the last three session dates
            $sessions = $request['sessions'];
            foreach ($sessions as $session) {
                if ($session['date'] > $expectedAntepenultimateSessionDate) {
                    // proves at least one session is logged within the last 3 scheduled sessions
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the third last ideal session date
     *
     * @param array $pairingDays - holds all pairing days
     *
     * @return string date - date of third last ideal session
     */
    private function getAntepenultimateSessionDate($pairingDays)
    {
        $now = Carbon::now();

        $lastSessionDate = $now->subWeekday();
        $lastSessionDay = strtolower($lastSessionDate->format('l'));

        // get the last day when a session was to occur from pairing days
        while (!in_array($lastSessionDay, $pairingDays)) {
            $lastSessionDate = $lastSessionDate->subWeekday();
            $lastSessionDay = strtolower($lastSessionDate->format('l'));
        }

        // get the index of the third last session day in pairing days
        $sessionIndex = array_search($lastSessionDay, $pairingDays);

        // create an array of the last three sessions
        $lastThreeSessionDays = [];

        while (count($lastThreeSessionDays) < 3) {
            if ($sessionIndex < 0) {
                $sessionIndex = $sessionIndex + count($pairingDays);
                $lastThreeSessionDays[] = $pairingDays[$sessionIndex];

            } else {
                $lastThreeSessionDays[] = $pairingDays[$sessionIndex];
            }

            $sessionIndex--;
        }
        // move a step back till third from last date is found
        $sessionDate = $lastSessionDate;
        for ($counter = 1; $counter < count($lastThreeSessionDays); $counter++) {
            $sessionDay = $lastThreeSessionDays[$counter];
            $sessionDate = date(
                "Y-m-d H:i:s",
                strtotime("last $sessionDay", strtotime($sessionDate))
            );
        }

        return $sessionDate;
    }
}
