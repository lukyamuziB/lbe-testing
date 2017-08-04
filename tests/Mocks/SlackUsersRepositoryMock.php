<?php

namespace Test\Mocks;

use App\Repositories\SlackUsersRepository;

class SlackUsersRepositoryMock extends SlackUsersRepository
{
    /**
     * Populate $model with a mock of what the redis cache will normally return
     */
    public function make()
    {
        $this->model = [
            "CC321" => (object)[
                "id" => "CC321",
                "email" => "adebayo.adesanya@andela.com",
                "handle" => "@bayo",
                "fullname" => "Adebayo Adesanya"
            ],
            "CC322" => (object)[
                "id" => "CC322",
                "email" => "inumidun.amao@andela.com",
                "handle" => "@amao",
                "fullname" => "Inumidun Amao"
            ],
        ];
    }
}
