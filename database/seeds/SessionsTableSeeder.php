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
        $faker = Faker\Factory::create();
        $limit = 20;

        for ($i = 0; $i < $limit; $i++) {

            DB::table('sessions')->insert(
                [
                    'request_id' => $faker->numberBetween($min = 1, $max = 20),
                    'date' => Carbon::today(),
                    'start_time' => Carbon::now()->addHour(12),
                    'end_time' => Carbon::now()->addHour(14),
                    'mentee_approved' => $faker->boolean(),
                    'mentor_approved' => $faker->boolean(),
                    'mentee_logged_at' => Carbon::now(),
                    'mentor_logged_at' => Carbon::now()
                ]
            );
        }
    }
}
