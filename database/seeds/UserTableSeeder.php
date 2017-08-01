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
        $limit = 3;

        $user_ids = ['-K_nkl19N6-EGNa0W8LF', '-KXGy1MT1oimjQgFim7u', '-KesEogCwjq6lkOzKmLI'];

        for ($i = 0; $i < $limit; $i++) {
            DB::table('users')->insert([
                'user_id' => $user_ids[$i],
                'slack_id' => ($i + 1) * 2,
                'email' => $faker->unique()->email
            ]);
        }    
    }
}
