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

        $this->seedBaseData($requestsCount, $requestUpperLimit, $today);
        $this->seedSkillMentorsData();
    }

    /**
     * Skill mentors specific sessions seeds.
     *
     * @return void
     */
    private function seedSkillMentorsData()
    {
        $customRequestId = 21;
        for ($i = 0; $i < 5; $i++) {
            $requestCreationDate = Carbon::now()->copy()->subMonths(23 - $i);
            $secondPairingDay = $requestCreationDate->next(Carbon::MONDAY)->addWeek();

            DB::table('sessions')->insert(
                [
                    'request_id' => $customRequestId,
                    'date' => $secondPairingDay,
                    'start_time' => Carbon::instance($secondPairingDay)->addHour(13)->format("Y-m-d H:i:s"),
                    'end_time' => Carbon::instance($secondPairingDay)->addHour(14)->format("Y-m-d H:i:s"),
                    'mentee_approved' => true,
                    'mentor_approved' => true,
                    'mentee_logged_at' => Carbon::today()->subHours(2)->format("Y-m-d H:i:s"),
                    'mentor_logged_at' => Carbon::now()->subHours(2)->format("Y-m-d H:i:s")
                ]
            );

            $customRequestId += 1;
        }
    }

    /**
     * Base sessions seeds.
     *
     * @return void
     */
    private function seedBaseData($requestsCount, $requestUpperLimit, $today)
    {
        for ($i = 0; $i < $requestsCount; $i++) {
            $requestCreationDate = $today->copy()->subMonths($requestUpperLimit - $i);
            $secondPairingDay = $requestCreationDate->next(Carbon::MONDAY)->addWeek();

            $isMentorApproved = $i % 2 === 0 ? true : null;
            $isMenteeApproved = $i % 3 === 0 ? true : null;

            DB::table('sessions')->insert(
                [
                    'request_id' => $i + 1,
                    'date' => $secondPairingDay,
                    'start_time' => Carbon::instance($secondPairingDay)->addHour(13)->format("Y-m-d H:i:s"),
                    'end_time' => Carbon::instance($secondPairingDay)->addHour(14)->format("Y-m-d H:i:s"),
                    'mentee_approved' => ($i === 18) ? false: $isMenteeApproved,
                    'mentor_approved' => ($i === 18) ? false: $isMentorApproved,
                    'mentee_logged_at' => Carbon::today()->subHours(2)->format("Y-m-d H:i:s"),
                    'mentor_logged_at' => Carbon::now()->subHours(2)->format("Y-m-d H:i:s")
                ]
            );
        }
    }
}
