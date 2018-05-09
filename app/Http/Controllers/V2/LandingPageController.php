<?php

namespace App\Http\Controllers\V2;

use Illuminate\Http\Response;

class LandingPageController extends Controller
{
    use RESTActions;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Create application details
     *
     * @return Response object
     */
    public function get()
    {
        $response["Company"] = "Andela";
        $response["Application"] = "Lenken";
        $response["Version"] = "2.0";
        $response["URL"] = "https://lenken.andela.com";
        $response["Documentation"] = "https://lenken.docs.apiary.io";

        return $this->respond(Response::HTTP_OK, $response);
    }
}
