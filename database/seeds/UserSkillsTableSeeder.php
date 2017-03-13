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

        for ($i = 0; $i < $limit; $i++) {
            DB::table('user_skills')->insert([
                'user_id' => $faker->numberBetween($min = 1, $max = 20),
                'skill_id' => $faker->randomElement($array = range(1, 50)),
                'created_at' => $faker->date($format = 'Y-m-d H:i:s', $max = 'now'),
                'updated_at' => null
            ]);
        }
    }
}
