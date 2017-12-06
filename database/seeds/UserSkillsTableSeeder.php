<?php

use Illuminate\Database\Seeder;

class UserSkillsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $limit = 49;
        $users = [
            "-KXGy1MimjQgFim7u",
            "-K_nkl19N6-EGNa0W8LF",
            "-KXGy1MT1oimjQgFim7u",
            "-KesEogCwjq6lkOzKmLI",
        ];

        for ($i = 1; $i < $limit; $i++) {
            DB::table('user_skills')->insert([
                'user_id' => $users[$faker->numberBetween(0, 3)],
                'skill_id' => $i,
                'created_at' => $faker->date($format = 'Y-m-d H:i:s', $max = 'now'),
                'updated_at' => null
            ]);
        }
    }
}
