<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call("UserTableSeeder");
        $this->call("SkillsTableSeeder");
        $this->call("UserSkillsTableSeeder");
        $this->call("StatusTableSeeder");
        $this->call("RequestsTableSeeder");
        $this->call("RequestSkillsTableSeeder");
        $this->call("RequestLogsTableSeeder");
        $this->call("SessionsTableSeeder");
        $this->call("RatingsTableSeeder");
        $this->call("NotificationsTableSeeder");
        $this->call("UserNotificationsTableSeeder");
        $this->call("RequestExtensionsTableSeeder");
        $this->call("RequestTypeTableSeeder");
        $this->call("RoleTableSeeder");
        $this->call("SessionCommentsTableSeeder");
    }
}
