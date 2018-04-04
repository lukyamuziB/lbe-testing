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

        for ($i = 0; $i < $limit; $i++) {
            $today = Carbon::now();
            $created_at = $today->subMonths($upperLimit - $i);
            $createdRequest = Request::create(
                [
                    'created_by' => $faker->randomElement(['-K_nkl19N6-EGNa0W8LF']),
                    'request_type_id' => $faker->randomElement([2]),
                    'title' => $faker->sentence(6, true),
                    'description' => $faker->text($maxNbChars = 300),
                    'status_id' => ($i%2 === 0 ? 1 : 2),
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
                    "user_id" => $createdRequest->created_by,
                    "role_id" => $createdRequest->request_type_id == 2 ? 2 : 1,
                    "request_id" => $createdRequest->id
                ]
            );
            if ($createdRequest->status_id == 2 || $createdRequest->status_id == 3) {
                RequestUsers::create(
                    [
                        "user_id" => "-KXGy1MimjQgFim7u",
                        "role_id" => $requestOwner->role_id == 2 ? 1 : 2,
                        "request_id" => $createdRequest->id
                    ]
                );
            }
        }
    }
}
