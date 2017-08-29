<?php

namespace Test\Mocks;

use App\Clients\FreckleClient;

class FreckleClientMock extends FreckleClient
{
    /**
     * Post a single entry
     *
     * @param array $data entry data i.e date, user_id, minutes, description, project_id and source_url
     *
     * @return array $entry of an entry
     */
    public function postEntry($data)
    {
        return ["message" => "success", "data" => $data];
    }

    /**
     * Retrieve specific users using email address
     *
     * @param string $email_address the user's email address
     *
     * @return array $user of a user
     */
    public function getUserByEmail($email_address)
    {
        $user = [
            [
                "email" => $email_address,
                "id" => 2
            ]
        ];

        return $user;
    }
}
