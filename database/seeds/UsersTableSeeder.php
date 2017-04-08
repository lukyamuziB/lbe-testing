<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
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
            DB::table('users')->insert([
                'name' => $faker->name,
                'email' => $faker->unique()->email,
                'user_id' => $faker->unique()->regexify('-[a-zA-Z]{5}-[a-zA-Z]{13}'),
                'role' => $faker->randomElement($array = array('Fellow', 'Learning', 'Staff')),
                'firstname' => $faker->firstName,
                'lastname' => $faker->lastName,
                'profile_pic' => $faker->imageUrl(50, 60),
                'created_at' => $faker->date($format = 'Y-m-d H:i:s', $max = 'now'),
                'updated_at' => null
            ]);
        }
    }
}
