<?php

use Illuminate\Database\Seeder;

class RequestTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $limit = 2;
        $role = array("SEEKING MENTEE", "SEEKING MENTOR");
        for ($i = 0; $i < $limit; $i++) {
            DB::table("request_type")->insert([
                "name" => $role[$i]
            ]);
        }
    }
}
