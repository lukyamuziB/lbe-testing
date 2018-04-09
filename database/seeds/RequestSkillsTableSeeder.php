<?php

use Illuminate\Database\Seeder;

class RequestSkillsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $limit = 1;

        $this->seedBaseData($faker, $limit);
        $this->seedSkillMentorsData();
    }

    /**
     * Skill mentors specific request skills seeds.
     *
     * @return void
     */
    private function seedSkillMentorsData()
    {
        $customRequestId = 21;
        for ($i = 0; $i < 5; $i++) {
            DB::table('request_skills')->insert([
                'request_id' => $customRequestId,
                'skill_id' => "18",
                'type' => 'primary'
            ]);
            $customRequestId += 1;
        }
    }

    /**
     * Base request skills seeds.
     *
     * @return void
     */
    private function seedBaseData($faker, $limit)
    {
        for ($i = 0; $i < $limit; $i++) {
            DB::table('request_skills')->insert([
                'request_id' => $i + 1,
                'skill_id' => $faker->numberBetween($min = 1, $max = 50),
                'type' => 'primary'
            ]);
        }
    }
}
