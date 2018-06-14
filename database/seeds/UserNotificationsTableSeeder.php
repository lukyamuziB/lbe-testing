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
        $notification_id = ["REQUESTS_MATCHING_USER_SKILLS", "REQUEST_ACCEPTED_OR_REJECTED",
        "SESSION_NOTIFICATIONS", "FILE_NOTIFICATIONS",
        "INDICATES_INTEREST", "WITHDRAWN_INTEREST",
        "MATCHING_OPEN_REQUEST_SKILLS"];

        for ($i = 0; $i < $limit; $i++) {
            foreach ($notification_id as $id) {
                DB::table('user_notifications')->insert(
                    [
                    'user_id' => $user_ids[$i],
                    'id' => $id,
                    'in_app' => true,
                    'email' => true
                    ]
                );
            }
        } 
    }
}
