<?php

use Illuminate\Database\Seeder;

class RequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $limit = 15;

        for ($i = 0; $i < $limit; $i++) {
            DB::table('requests')->insert([
                'mentee_id' => $faker->numberBetween($min = 1, $max = 20),
                'mentor_id' => $faker->numberBetween($min = 1, $max = 20),
                'title' => $faker->sentence($nbWords = 6, $variableNbWords = true),
                'description' => $faker->text($maxNbChars = 300),
                'status_id' => $faker->randomElement($array = array(1, 2)),
                'created_at' => $faker->date($format = 'Y-m-d H:i:s', $max = 'now'),
                'updated_at' => null
            ]);
        }
    }
}
