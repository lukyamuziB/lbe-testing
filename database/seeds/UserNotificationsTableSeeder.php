<?php

use Illuminate\Database\Seeder;

class UserNotificationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $limit = 3;

        $user_ids = ['-KXGy1MT1oimjQgFim7u', '-KesEogCwjq6lkOzKmLI',
        '-K_nkl19N6-EGNa0W8LF'];
        $notification_id = ["INDICATES_INTEREST", "SELECTED_AS_MENTOR",
        "LOG_SESSIONS_REMINDER", "WEEKLY_REQUESTS_REPORTS"];

        for ($i = 0; $i < $limit; $i++) {
            foreach ($notification_id as $id) {
                DB::table('user_notifications')->insert(
                    [
                    'user_id' => $user_ids[$i],
                    'id' => $id,
                    'slack' => true,
                    'email' => false
                    ]
                );
            }
        } 
    }
}
