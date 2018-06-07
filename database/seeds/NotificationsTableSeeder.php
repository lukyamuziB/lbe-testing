<?php

use Illuminate\Database\Seeder;

class NotificationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ["REQUESTS_MATCHING_YOUR_SKILLS" => ["default" => "in_app",
            "description" =>
            "When there are new requets in the pool matching skills in your profile"]],
            ["REQUEST_ACCEPTED_OR_REJECTED" => ["default" => "in_app",
            "description" =>
            "Whenever a request is either accepted or rejected"]],
            ["SESSION_NOTIFICATIONS" => ["default" => "in_app",
            "description" =>
            "When a session is logged, accepted or rejected"]],
            ["FILE_NOTIFICATIONS" => ["default" => "in_app",
            "description" =>
            "Whenever a file is uploaded or deleted"]],
            ["INDICATES_INTEREST" => ["default" => "in_app",
            "description" =>
             "Whenever anyone indicates interest on a request you created"]],
            ["WITHDRAWN_INTEREST" => ["default" => "in_app",
            "description" =>
            "Whenever anyone withdraws a request after showing interest on the request"]],
            ["REQUESTS_MATCHING_YOUR_OPEN_SKILLS" => ["default" => "in_app",
            "description" =>
            "Whenever a request is created for a skill you have an opening for"]],
        ];
        foreach ($data as $entry) {
            foreach ($entry as $title => $body) {
                DB::table('notifications')->insert(
                    [
                        'id' => $title,
                        'default' => $body["default"],
                        'description' => $body["description"],
                    ]
                );
            }
        }
    }
}
