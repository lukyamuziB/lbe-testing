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
            DB::table('ratings')->insert(
                [
                    'user_id' => $faker->randomElement(
                        ['-K_nkl19N6-EGNa0W8LF', '-KXGy1MT1oimjQgFim7u', '-KesEogCwjq6lkOzKmLI']
                    ),
                    'session_id' => $i + 1,
                    'values' => json_encode(
                        [
                            'availability' => '1',
                            'usefulness' => '2',
                            'reliability' => '1',
                            'knowledge' => '2',
                            'teaching' => '3'
                        ]
                    ),
                    'scale' => 5
                ]
            );
        }
    }
}
