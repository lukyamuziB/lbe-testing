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
            $created_at = $faker->dateTimeBetween(
                '-3 years',
                '-2 weeks',
                date_default_timezone_get(),
                'Y-m-d H:i:s'
            );
            DB::table('requests')->insert(
                [
                    'mentee_id' => $faker->randomElement(['-K_nkl19N6-EGNa0W8LF']),
                    'mentor_id' => $i === 19 ? '-KesEogCwjq6lkOzKmLI' :
                    $faker->randomElement(
                        ['-KesEogCwjq6lkOzKmLI', '-KXGy1MT1oimjQgFim7u']
                    ),
                    'title' => $faker->sentence($nbWords = 6, $variableNbWords = true),
                    'description' => $faker->text($maxNbChars = 300),
                    'status_id' => ($i%2 === 0 ? 1 : 2),
                    'created_at' => $created_at,
                    'updated_at' => null,
                    'match_date' => $faker->dateTimeBetween($created_at, $created_at->format('Y-m-d H:i:s').' +7 days'),
                    'duration' => $faker->numberBetween($min = 1, $max = 12),
                    'pairing' => json_encode(
                        [
                        'start_time' => '01:00',
                        'end_time' => '02:00',
                        'days' => ['monday'],
                        'timezone' => 'EAT']
                    ),
                'location' => $faker->randomElement(['Nairobi', 'Lagos'])
                ]
            );
        }
    }
}
