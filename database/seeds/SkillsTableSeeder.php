<?php

use Illuminate\Database\Seeder;

class SkillsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $limit = 50;
        $skills = array(
            'Git & GitHub',
            'C/C++',
            'C#',
            'Objective C',
            'Java',
            'Swift',
            'Assembly',
            'ASP',
            'Perl',
            'Python',
            'Ruby',
            'Ruby on Rails',
            'HTML/CSS',
            'JavaScript',
            'JQuery',
            'AngularJS 1',
            'AngularJS 2',
            'ReactJS',
            'React Native',
            'Titanium',
            'NativeScript',
            'Ionic Framework',
            'Backbone.js',
            'Node, NPM & Express',
            'Ember.js',
            'Loadash',
            'Yarn',
            'Google Web Toolkit',
            'Knockout',
            'MooTools',
            'Vue.js',
            'Underscore',
            'Redux',
            'RxJS',
            'Grunt & Gulp',
            'Babel',
            'Webpack',
            'PHP',
            'CodeIgniter',
            'Laravel',
            'Lumen',
            'Kohana',
            'Phalcon',
            'Slim',
            'Zend',
            'Symfony',
            'DevOps',
            'Progressive Web Apps',
            'Nginx',
            'Apache Server',
            'Adobe Photoshop',
            'Adobe Illustrator',
            'Adobe Fireworks'
        );

        for ($i = 0; $i < $limit; $i++) {
            DB::table('skills')->insert([
                'name' => $faker->unique()->randomElement($array = $skills),
            ]);
        }
    }
}
