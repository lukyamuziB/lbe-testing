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
            "a user recieves notification when someone indicates interest"]],
            ["SELECTED_AS_MENTOR" => ["default" => "slack",
            "description" => 
            "a user recieves notification when he\she is selected as mentor"]],
            ["LOG_SESSIONS_REMINDER" => ["default" => "slack",
            "description" => 
            "a user recieves notification when someone logs session remainder"]],
            ["WEEKLY_REQUESTS_REPORTS" => ["default" => "email",
            "description" => 
            "a user recieves notification when there's a weekly request report"]]
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
