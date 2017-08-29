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
        $limit = 20;

        for ($i = 0; $i < $limit; $i++) {

            $isMentorApproved = $i % 3 === 0 ? true : null;
            $isMenteeApproved = $i % 2 === 0 ? true : null;

            DB::table('sessions')->insert(
                [
                    'request_id' => $i+1,
                    'date' => Carbon::today(),
                    'start_time' => Carbon::now()->addHour(12),
                    'end_time' => Carbon::now()->addHour(14),
                    'mentee_approved' => $isMenteeApproved,
                    'mentor_approved' => $isMentorApproved,
                    'mentee_logged_at' => Carbon::today(),
                    'mentor_logged_at' => Carbon::now()
                ]
            );
        }
    }
}
