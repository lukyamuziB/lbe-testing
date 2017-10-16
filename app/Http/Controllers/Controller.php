<?php

namespace App\Http\Controllers;

use App\Models\Request as MentorshipRequest;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Gets the value of the request param
     *
     * @param object $request - the request object
     * @param string $key     - the key of the value to be retrieved
     *
     * @return string - the value of the request param
     */
    public function getRequestParams($request, $key)
    {
        return $request->input($key) ?? null;
    }
}
