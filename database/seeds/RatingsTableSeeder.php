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

        $this->seedBaseData($faker, $limit);
        $this->seedSKillMentorsData($faker);
    }

    /**
     * Skill mentors specific ratings seeds.
     *
     * @return void
     */
    private function seedSKillMentorsData($faker)
    {
        $customSessionId = 21;
        for ($i = 0; $i < 5; $i++) {
            DB::table('ratings')->insert(
                [
                    'user_id' => $faker->randomElement(['-KXGy1MimjQgFim7u']),
                    'session_id' => $customSessionId,
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
            $customSessionId += 1;
        }
    }

    /**
     * Base ratings seeds.
     *
     * @return void
     */
    private function seedBaseData($faker, $limit)
    {
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
