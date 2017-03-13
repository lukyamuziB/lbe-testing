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
        $this->call('UsersTableSeeder');
        $this->call('SkillsTableSeeder');
        $this->call('UserSkillsTableSeeder');
        $this->call('StatusTableSeeder');
        $this->call('RequestsTableSeeder');
        $this->call('RequestSkillsTableSeeder');
        $this->call('RequestInterestsTableSeeder');
        $this->call('RequestLogsTableSeeder');
        $this->call('RatingsTableSeeder');
    }
}
