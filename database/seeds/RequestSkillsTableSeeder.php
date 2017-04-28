<?php

use Illuminate\Database\Seeder;

class RequestSkillsTableSeeder extends Seeder
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
            DB::table('request_skills')->insert([
                'request_id' => $faker->numberBetween($min = 1, $max = 15),
                'skill_id' => $faker->numberBetween($min = 1, $max = 50),
                'type' => 'primary'
            ]);
        }
    }
}
