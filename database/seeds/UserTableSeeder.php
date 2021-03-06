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
        $limit = 4;

        $user_ids = ['-KXGy1MT1oimjQgFim7u', '-K_nkl19N6-EGNa0W8LF', '-KXGy1MimjQgFim7u', '-KesEogCwjq6lkOzKmLI'];

        $emails = ["adebayo.adesanya@andela.com", "inumidun.amao@andela.com",
            "ichiato.ikkin@andela.com", "felistas.ngumi@andela.com"];

        for ($i = 0; $i < $limit; $i++) {
            DB::table('users')->insert([
                'id' => $user_ids[$i],
                'slack_id' => "1En-kEn{($i + 1) * 2}",
                'email' => $emails[$i]
            ]);
        }
    }
}
