<?php

use Illuminate\Database\Seeder;

class SessionCommentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $limit = 3;
        $customSessionId = 1;
        $users = [
        "-K_nkl19N6-EGNa0W8LF",
        "-KesEogCwjq6lkOzKmLI",
        ];

        for ($i = 1; $i < $limit; $i++) {
            DB::table('session_comments')->insert(
                [
                'session_id' => $customSessionId,
                'user_id' => $users[$faker->numberBetween(0, 1)],
                'comment' => $faker->sentence(6, true),
                ]
            );
        }
    }
}
