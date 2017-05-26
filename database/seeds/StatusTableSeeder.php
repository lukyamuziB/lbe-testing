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
        $limit = 4;
        $status = array('open', 'matched', 'closed', 'cancelled');
        for ($i = 0; $i < $limit; $i++) {
            DB::table('status')->insert([
                'name' => $status[$i]
            ]);
        }
    }
}
