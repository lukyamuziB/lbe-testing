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
        $limit = 100;
        $users = [
            "-K_nkl19N6-EGNa0W8LF",
            "-KXGy1MTimjQgFim7u",
            "-KXGy1MTiQgFim7",
            "-KXGyddsds2imjQgFim7u",
            "-K1MTimjQgFim7u",
            "-KXGywq1ew-eTimjQgFim7u"
        ];

        for ($i = 0; $i < $limit; $i++) {
            DB::table('user_skills')->insert([
                'user_id' => $users[$faker->numberBetween(0, 5)],
                'skill_id' => $faker->randomElement($array = range(1, 50)),
                'created_at' => $faker->date($format = 'Y-m-d H:i:s', $max = 'now'),
                'updated_at' => null
            ]);
        }
    }
}
