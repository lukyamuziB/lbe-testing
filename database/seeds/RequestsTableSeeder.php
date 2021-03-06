<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Request;
use App\Models\RequestUsers;

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

        $this->seedBaseData($faker, $limit, $upperLimit);
        $this->seedSkillMentorsData($faker, $limit, $upperLimit);
        $this->seedRequestForSessionsSeed($faker, $limit, $upperLimit);
        $this->seedRequestForUserHistory($faker, $limit, $upperLimit);
    }

    /**
     * Skill mentors specific requests seeds.
     *
     * @return void
     */
    private function seedSkillMentorsData($faker)
    {
        for ($i = 0; $i < 5; $i++) {
            $today = Carbon::now();
            $created_at = $today->subMonths(23 - $i);

            $createdRequest = Request::create(
                [
                    'created_by' => $faker->randomElement(['-K_nkl19N6-EGNa0W8LF']),
                    'request_type_id' => 1,
                    'title' => $faker->sentence(6, true),
                    'description' => $faker->text(300),
                    'status_id' => ($i%2 === 0 ? 1 : 2),
                    'interested' => (
                    ($i === 4) ? ['-K_nkl19N6-EGNa0W8LF']
                        : ($i === 2) ? ['-KesEogCwjq6lkOzKmLI']
                        : null
                    ),
                    'created_at' => $created_at,
                    'updated_at' => null,
                    'match_date' => $created_at->addWeek(),
                    'duration' => $i+2,
                    'pairing' =>
                        [
                            'start_time' => '01:00',
                            'end_time' => '02:00',
                            'days' => ['monday'],
                            'timezone' => 'EAT'
                        ],
                        'location' => $faker->randomElement(
                            [
                                'Nairobi',
                                'Lagos',
                                'kampala'
                            ]
                        )
                ]
            );

            RequestUsers::create(
                [
                    "user_id" => $createdRequest->created_by->id,
                    "role_id" => 2,
                    "request_id" => $createdRequest->id
                ]
            );

            RequestUsers::create(
                [
                    "user_id" => "-KXGy1MimjQgFim7u",
                    "role_id" => 1,
                    "request_id" => $createdRequest->id
                ]
            );
        }
    }

    /**
     * Seed requests for user history.
     *
     * @return void
     */
    private function seedRequestForUserHistory($faker)
    {
        for ($i = 0; $i < 5; $i++) {
            $today = Carbon::now();
            $created_at = $today->subMonths(23 - $i);

            $createdRequest = Request::create(
                [
                    'created_by' => $faker->randomElement(['-KesEogCwjq6lkOzKmLI']),
                    'request_type_id' => 1,
                    'title' => $faker->sentence(6, true),
                    'description' => $faker->text(300),
                    'status_id' => ($i%3 === 0 ? 2 : 3),
                    'interested' => (
                    ($i === 6) ? ['-K_nkl19N6-EGNa0W8LF']
                        ['-KesEogCwjq6lkOzKmLI']
                        : null
                    ),
                    'created_at' => $created_at,
                    'updated_at' => null,
                    'match_date' => $created_at->addWeek(),
                    'duration' => $i+2,
                    'pairing' =>
                        [
                            'start_time' => '01:00',
                            'end_time' => '02:00',
                            'days' => ['monday'],
                            'timezone' => 'EAT'
                        ],
                        'location' => $faker->randomElement(
                            [
                                'Nairobi',
                                'Lagos',
                                'kampala'
                            ]
                        )
                ]
            );

            RequestUsers::create(
                [
                    "user_id" => $createdRequest->created_by->id,
                    "role_id" => 2,
                    "request_id" => $createdRequest->id
                ]
            );

            RequestUsers::create(
                [
                    "user_id" => "-KXGy1MimjQgFim7u",
                    "role_id" => 1,
                    "request_id" => $createdRequest->id
                ]
            );
        }
    }
    /**
     * Seed request for sessions seed.
     *
     * @return void
     */
    private function seedRequestForSessionsSeed($faker)
    {
        for ($i = 0; $i < 5; $i++) {
            $today = Carbon::now();
            $created_at = $today->subMonths(23 - $i);

            $createdRequest = Request::create(
                [
                    'created_by' => (
                        ($i%3 === 0) ? '-K_nkl19N6-EGNa0W8LF':
                            '-KesEogCwjq6lkOzKmLI'
                        ),
                    'request_type_id' => 1,
                    'title' => $faker->sentence(6, true),
                    'description' => $faker->text(300),
                    'status_id' => ($i%3 === 0 ? 2 : 3),
                    'interested' => (
                    ($i%3 === 0) ? ['-KesEogCwjq6lkOzKmLI']:
                        ['-K_nkl19N6-EGNa0W8LF']
                    ),
                    'created_at' => $created_at,
                    'updated_at' => null,
                    'match_date' => $created_at->addWeek(),
                    'duration' => $i+2,
                    'pairing' =>
                        [
                            'start_time' => '01:00',
                            'end_time' => '02:00',
                            'days' => ['monday'],
                            'timezone' => 'EAT'
                        ],
                        'location' => $faker->randomElement(
                            [
                                'Nairobi',
                                'Lagos',
                                'kampala'
                            ]
                        )
                ]
            );

            if ($createdRequest->status_id == 2) {
                RequestUsers::create(
                    [
                        "user_id" => "-KesEogCwjq6lkOzKmLI",
                        "role_id" => 1,
                        "request_id" => $createdRequest->id
                    ]
                );
            }


            RequestUsers::create(
                [
                    "user_id" => "-KXGy1MimjQgFim7u",
                    "role_id" => 2,
                    "request_id" => $createdRequest->id
                ]
            );
        }
    }

    /**
     * Base requests seeds.
     *
     * @return void
     */
    private function seedBaseData($faker, $limit, $upperLimit)
    {
        for ($i = 0; $i < $limit; $i++) {
            $today = Carbon::now();
            $created_at = $today->subMonths($upperLimit - $i);
            $createdRequest = Request::create(
                [
                    'created_by' => $faker->randomElement(['-K_nkl19N6-EGNa0W8LF']),
                    'request_type_id' => $faker->randomElement([2]),
                    'title' => $faker->sentence(6, true),
                    'description' => $faker->text($maxNbChars = 300),
                    'status_id' => ($i === 18) ? 2 : ($i%2 === 0 ? 1 : 2),
                    'interested' => (
                    ($i === 20) ? ['-K_nkl19N6-EGNa0W8LF']
                        : ($i === 18) ? ['-KesEogCwjq6lkOzKmLI']
                        : null
                    ),
                    'created_at' => $created_at,
                    'updated_at' => null,
                    'match_date' => $created_at->addWeek(),
                    'duration' => $i+2,
                    'pairing' =>
                        [
                            'start_time' => '01:00',
                            'end_time' => '02:00',
                            'days' => ['monday'],
                            'timezone' => 'EAT'
                        ],
                    'location' => $faker->randomElement(
                        [
                            'Nairobi',
                            'Lagos',
                            'kampala'
                        ]
                    )
                ]
            );
            $requestOwner = RequestUsers::create(
                [
                    "user_id" => $createdRequest->created_by->id,
                    "role_id" => $createdRequest->request_type_id == 2 ? 2 : 1,
                    "request_id" => $createdRequest->id
                ]
            );

            if ($createdRequest->status_id == 2 || $createdRequest->status_id == 3) {
                RequestUsers::create(
                    [
                        "user_id" => "-KesEogCwjq6lkOzKmLI",
                        "role_id" => $requestOwner->role_id == 2 ? 1 : 2,
                        "request_id" => $createdRequest->id
                    ]
                );
            }
        }
    }
}
