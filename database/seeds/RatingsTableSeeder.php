<?php

use Illuminate\Database\Seeder;

class RatingsTableSeeder extends Seeder
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
            DB::table('ratings')->insert([
                'description' => $faker->paragraph($nbSentences = 2, $variableNbSentences = true),
                'star' => $faker->numberBetween($min = 1, $max = 5),
                'user_id' => $faker->numberBetween($min = 1, $max = 20),
                'request_id' => $faker->numberBetween($min = 1, $max = 15),
                'created_at' => $faker->date($format = 'Y-m-d H:i:s', $max = 'now'),
                'updated_at' => null
            ]);
        }
    }
}
