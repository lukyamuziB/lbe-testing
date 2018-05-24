<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Response;

class DeprecationMessageController extends Controller
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
     * Indicate that version one is deprecated
     *
     * @return Response object
     */
    public function get()
    {
        $response["V1 deprecated"] = "This version of the API has been deprecated";

        return $this->respond(Response::HTTP_NOT_FOUND, $response);
    }
}
