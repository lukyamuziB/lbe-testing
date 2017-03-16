<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RequestsController extends Controller {

    const MODEL = "App\Requests";

    use RESTActions;

    public function add(Request $request)
    {
        $m = self::MODEL;
        $this->validate($request, $m::$rules);
        $user = $request->user();
        $user_array = array('mentee_id' => $user->id, "status_id" => 2);
        $record = array_merge($request->all(), $user_array);
        return $this->respond(Response::HTTP_CREATED, $m::create($record));
    }

}
