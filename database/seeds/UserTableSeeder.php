<?php

use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $limit = 1;

        for ($i = 0; $i < $limit; $i++) {
            DB::table('users')->insert([
                'user_id' => $faker->randomElement(['-K_nkl19N6-EGNa0W8LF']),
                'slack_id' => ($i + 1) * 2,
                'email' => $faker->unique()->email
            ]);
        }    
    }
}
