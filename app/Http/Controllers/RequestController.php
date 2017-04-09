<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\Exception;
use Lcobucci\JWT\Parser;
use App\Exceptions\NotFoundException;
use App\Exceptions\AccessDeniedException;
use App\User;

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

    /**
     * Edit a mentorship request
     *
     * @param  integer $id Unique ID of the mentorship request
     */
    public function put(Request $request, $id)
    {
        $m = self::MODEL;

        $this->validate($request, array());

        try {
            $mentorship_request = $m::find(intval($id));

            if (is_null($mentorship_request)) {
                throw new NotFoundException("the specified request was not found", 1);
            }

            $parsed_token = (new Parser())->parse((string) $request->bearerToken());
            $current_user = $parsed_token->getClaim('UserInfo');

            if ($current_user->id !== $mentorship_request->mentee_id) {
                throw new AccessDeniedException("you don't have permission to edit the mentorship request", 1);
            }

        } catch (NotFoundException $exception) {
            return $this->respond(Response::HTTP_NOT_FOUND, ["message" => $exception->getMessage()]);
        } catch (AccessDeniedException $exception) {
            return $this->respond(Response::HTTP_FORBIDDEN, ["message" => $exception->getMessage()]);
        } catch (Exception $exception) {
            return $this->respond(Response::HTTP_BAD_REQUEST, ["message" => $exception->getMessage()]);
        }

        $mentorship_request->update($request->all());
        return $this->respond(Response::HTTP_CREATED, $mentorship_request);
    }
}
