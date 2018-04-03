<?php

namespace Tests\App\Models;

use TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    /**
     * Test setup.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        User::create(
            [
                "user_id" => "-K_nkl1kolop909",
                "email" => "timothy.kyadondo@andela.com",
                "slack_id" => "C655PE124",
            ]
        );
    }

    /**
     * Test fullname is returned when accessed.
     *
     * @return void
     */
    public function testGetFullnameAttributeSuccess()
    {
        $user = User::find("-K_nkl1kolop909");
        $this->assertEquals("Timothy Kyadondo", $user->fullname);
    }

    /**
     * Test to gets mentors average rating and email
     *
     * @return array
     */
    public function testGetMentorsAverageRatingAndEmail()
    {
        $mentors = [
            "-KXGy1MimjQgFim7u" => [
                [
                    "values" => "{\"knowledge\":\"2\",\"teaching\":\"3\",\"reliability\":\"1\",
                                    \"availability\":\"1\",\"usefulness\":\"2\"}",
                    "scale" => 5,
                    "user_id" =>  "-KXGy1MimjQgFim7u",
                    "user" => [
                        "email" => "adebayo.adesanya@andela.com"
                    ]
                ],
                [
                    "values" => "{\"knowledge\":1,\"teaching\":1,\"reliability\":1,
                                    \"availability\":1,\"usefulness\":1}",
                    "scale" => 5,
                    "user_id" => "-KXGy1MimjQgFim7u",
                    "user" => [
                        "email" => "adebayo.adesanya@andela.com"
                    ]
                ]
            ],
            "-L7ALN5ifcQLQjJ8nAz8" => [
                [
                    "values" => "{\"teaching\":1,\"reliability\":1,\"availability\":1,
                                    \"usefulness\":1,\"knowledge\":1}",
                    "scale" => 5,
                    "user_id" => "-L7ALN5ifcQLQjJ8nAz8",
                    "user" => [
                        "email" => "delight.balogun@andela.com"
                    ]
                ]
            ],
            "-K_nkl19N6-EGNa0W8LF" => [
                [
                    "values" => "{\"knowledge\":\"2\",\"teaching\":\"3\",\"reliability\":\"1\",
                                    \"availability\":\"1\",\"usefulness\":\"2\"}",
                    "scale" => 5,
                    "user_id" => "-K_nkl19N6-EGNa0W8LF",
                    "user" => [
                        "email" => "inumidun.amao@andela.com"
                    ]
                ]
            ],
            "-L4j_59h7xJAbieX7gpa" => [
                [
                    "values" => "{\"usefulness\":\"5\",\"teaching\":\"3\",\"reliability\":\"5\",
                                    \"knowledge\":\"1\",\"availability\":\"1\"}",
                    "scale" => 5,
                    "user_id" => "-L4j_59h7xJAbieX7gpa",
                    "user" => [
                        "email" => "rukayat.odukoya@andela.com"
                    ]
                ]
            ]
        ];

        $expectedResult = [
            [
                "average_rating" => "1.4",
                "email" => "adebayo.adesanya@andela.com",
                "session_count" => 2
            ],
            [
                "average_rating" => "1.0",
                "email" => "delight.balogun@andela.com",
                "session_count" => 1
            ],
            [
                "average_rating" => "1.8",
                "email" => "inumidun.amao@andela.com",
                "session_count" => 1
            ],
            [
                "average_rating" => "3.0",
                "email" => "rukayat.odukoya@andela.com",
                "session_count" => 1
            ]
        ];

        $averageRating = User::getMentorsAverageRatingAndEmail($mentors);
        $this->assertEquals($expectedResult, $averageRating);
    }
}
