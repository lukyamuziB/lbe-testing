<?php

use Illuminate\Database\Seeder;

class RequestLogsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $limit = 30;

        for ($i = 0; $i < $limit; $i++) {
            DB::table('request_logs')->insert([
                'request_id' => $faker->numberBetween($min = 1, $max = 15),
                'user_id' => $faker->numberBetween($min = 1, $max = 20),
                'type' => $faker->sentence($nbWords = 3, $variableNbWords = true),
                'description' => $faker->paragraph($nbSentences = 3, $variableNbSentences = true)
            ]);
        }
    }
}
