<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RequestExtensionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $limit = 5;
        for ($i = 1; $i < $limit; $i++) {
            DB::table("request_extensions")->insert(
                [
                    "request_id" => $i,
                    "approved" => null,
                    "created_at" => Carbon::now(),
                    "updated_at" => null
                ]
            );
        }
    }
}
