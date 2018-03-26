<?php

use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $limit = 2;
        $role = array("MENTOR", "MENTEE");
        for ($i = 0; $i < $limit; $i++) {
            DB::table("user_role")->insert([
                "name" => $role[$i]
            ]);
        }
    }
}
