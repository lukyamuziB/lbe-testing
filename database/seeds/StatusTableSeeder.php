<?php

use Illuminate\Database\Seeder;

class StatusTableSeeder extends Seeder
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
        $status = array('open', 'closed', 'matched');

        for ($i = 0; $i < $limit; $i++) {
            DB::table('status')->insert([
                'name' => $faker->unique()->randomElement($array = $status),
            ]);
        }
    }
}
