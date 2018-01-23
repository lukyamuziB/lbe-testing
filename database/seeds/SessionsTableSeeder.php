<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SessionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $requestsCount = 20;
        $requestUpperLimit = 23;
        $today = Carbon::now();

        for ($i = 0; $i < $requestsCount; $i++) {
            $requestCreationDate = $today->copy()->subMonths($requestUpperLimit - $i);
            $secondPairingDay = $requestCreationDate->next(Carbon::MONDAY)->addWeek();

            $isMentorApproved = $i % 3 === 0 ? true : null;
            $isMenteeApproved = $i % 2 === 0 ? true : null;

            DB::table('sessions')->insert(
                [
                    'request_id' => $i + 1,
                    'date' => $secondPairingDay,
                    'start_time' => Carbon::instance($secondPairingDay)->addHour(13)->format("Y-m-d H:i:s"),
                    'end_time' => Carbon::instance($secondPairingDay)->addHour(14)->format("Y-m-d H:i:s"),
                    'mentee_approved' => $isMenteeApproved,
                    'mentor_approved' => $isMentorApproved,
                    'mentee_logged_at' => Carbon::today()->subHours(2)->format("Y-m-d H:i:s"),
                    'mentor_logged_at' => Carbon::now()->subHours(2)->format("Y-m-d H:i:s")
                ]
            );
        }
    }
}
