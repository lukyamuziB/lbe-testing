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
        $user_id = array('mentee_id' => $user->id);
        $record = array_merge($request->all(), $user_id);
        return $this->respond(Response::HTTP_CREATED, $m::create($record));
    }

}
