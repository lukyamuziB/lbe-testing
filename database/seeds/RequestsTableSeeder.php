<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

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
        $upperLimit = 23;

        for ($i = 0; $i < $limit; $i++) {
            $today = Carbon::now();
            $created_at = $today->subMonths($upperLimit - $i);

            DB::table('requests')->insert(
                [
                    'mentee_id' => $faker->randomElement(['-K_nkl19N6-EGNa0W8LF']),
                    'mentor_id' => $i === 19 ? '-KesEogCwjq6lkOzKmLI' :
                        $faker->randomElement(
                            ['-KesEogCwjq6lkOzKmLI', '-KXGy1MT1oimjQgFim7u']
                        ),
                    'title' => $faker->sentence(6, true),
                    'description' => $faker->text($maxNbChars = 300),
                    'status_id' => ($i%2 === 0 ? 1 : 2),
                    'interested' => (
                    ($i === 20) ? json_encode(['-KesEogCwjq6lkOzKmLI'])
                        : ($i === 18) ? json_encode(['-K_nkl19N6-EGNa0W8LF'])
                        : null
                    ),
                    'created_at' => $created_at,
                    'updated_at' => null,
                    'match_date' => $created_at->addWeek(),
                    'duration' => $i+2,
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
