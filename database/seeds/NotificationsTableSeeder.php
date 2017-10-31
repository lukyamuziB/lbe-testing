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
            ["INDICATES_INTEREST" => ["default" => "slack",
            "description" => 
            "You'll be notified when someone indicates interest"]],
            ["SELECTED_AS_MENTOR" => ["default" => "slack",
            "description" => 
            "You'll be notified when someone selects you as a mentor"]],
            ["LOG_SESSIONS_REMINDER" => ["default" => "slack",
            "description" => 
            "You'll be notified when someone logs a session"]],
            ["WEEKLY_REQUESTS_REPORTS" => ["default" => "email",
            "description" => 
            "You'll receive a weekly list of open requests"]],
            ["REQUESTS_MATCHING_SKILLS" => ["default" => "slack",
            "description" => "You'll be notified when someone requests one of your skills"]]
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
