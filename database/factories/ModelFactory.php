<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    return [
        'id' => $faker->numberBetween($min = 1, $max = 9000),
        'firstname' => $faker->name,
        'lastname' => $faker->name,
        'email' => $faker->email,
        'role' => 'FELLOW',
        'profile_pic' => $faker->url
    ];
});

/**
 * Factory definition for model App\Models\Requests.
 */
$factory->define(App\Models\Requests::class, function (Faker\Generator $faker) {
    return [
        'user_id' => $faker->key,
    ];
});

/**
 * Factory definition for model App\Models\Skill.
 */
$factory->define(App\Models\Skill::class, function (Faker\Generator $faker) {
    return [
        'id' => $faker->numberBetween($min = 1, $max = 9000),
        'name' => $faker->name
    ];
});
