<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RequestController extends Controller {

    const MODEL = "App\Request";
    const MODEL2 = "App\RequestSkill";

    use RESTActions;

    public function add(Request $request)
    {
        $m = self::MODEL;
        $n = self::MODEL2;

        $this->validate($request, $m::$rules);
        $user = $request->user();
        $user_array = ['mentee_id' => $user->id, "status_id" => 2];
        $record = array_merge($request->all(), $user_array);

        $mentorship_request = $m::create($record);
        foreach ($record['skills'] as &$skill) {
            $n::create([
                'request_id' => $mentorship_request->id,
                'skill_id' => $skill
            ]);
        }

        return $this->respond(Response::HTTP_CREATED, $mentorship_request);
    }

}
