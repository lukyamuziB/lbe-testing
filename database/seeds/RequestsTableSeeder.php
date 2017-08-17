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
        $limit = 20;
        for ($i = 0; $i < $limit; $i++) {
            DB::table('requests')->insert(
                [
                    'mentee_id' => $faker->randomElement(['-K_nkl19N6-EGNa0W8LF']),
                    'mentor_id' => $faker->randomElement(['-KesEogCwjq6lkOzKmLI', '-KXGy1MT1oimjQgFim7u']),
                    'title' => $faker->sentence($nbWords = 6, $variableNbWords = true),
                    'description' => $faker->text($maxNbChars = 300),
                    'status_id' => ($i%2 === 0 ? 1 : 2),
                    'created_at' => $faker->date($format = 'Y-m-d H:i:s', $max = 'now'),
                    'updated_at' => null,
                    'match_date' => null,
                    'duration' => $faker->numberBetween($min = 1, $max = 12),
                    'pairing' => json_encode(
                        [
                        'start_time' => '2017-04-27T18:17:10+00:00',
                        'end_time' => '2017-05-27T18:17:10+00:00',
                        'days' => ['monday'],
                        'timezone' => 'EAT']
                    ),
                'location' => $faker->randomElement(['Nairobi', 'Lagos'])
                ]
            );
        }
    }
}
