<?php

use Illuminate\Database\Seeder;

class SessionRatingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $limit = 8;

        for ($i = 0; $i < $limit; $i++) {
            DB::table('session_ratings')->insert([
                'user_id'   => $faker->randomElement(['-K_nkl19N6-EGNa0W8LF']),
                'session_id'   =>  $faker->numberBetween($min = 1, $max = 15),
                'ratings' => json_encode([
                    'availability' => '1',
                    'usefulness'   => '2',
                    'reliability'  => '1',
                    'knowledge'    => '2',
                    'teaching'     => '3'
                ]),
                'scale' => ($i + 1) * 5
            ]);
        }
    }
}
