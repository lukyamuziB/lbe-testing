<?php
namespace App\Http\Controllers\V2;

use App\Models\Skill;
use App\Exceptions\Exception;
use Illuminate\Http\Response;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\NotFoundException;
use App\Models\Request as MentorshipRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Exceptions\ConflictException;
use App\Exceptions\BadRequestException;

/**
 * Class SkillController
 *
 * @package App\Http\Controllers\V2
 */
class SkillController extends Controller
{
    use RESTActions;

    /**
     * GET all skills that have at least one request
     *
     * @return json - JSON object containing skill(s)
     */
    public function getSkillsWithRequests()
    {
        $skills = Skill::whereHas("requestSkills")->get();

        return $this->respond(Response::HTTP_OK, $skills);
    }

    /**
     * Retrieve all skills in the database including the ones that have been
     * soft deleted with requestSkills.
     *
     * @param Request $request - HTTP Request object
     *
     * @return json - JSON object containing skill(s)
     */
    public function getSkills(Request $request)
    {
        $isTrashed = $request->input("isTrashed");

        if (strval($isTrashed) === "true") {
            $skills = Skill::withTrashed()->with(["requestSkills"])
            ->orderBy("created_at", "desc")->get();
        } else {
            $skills = Skill::all();
        }

        return $this->respond(Response::HTTP_OK, $skills);
    }

    /** Gets status count for each skill requested by location and start date and end date
     *
     * @param Request $request - the request object
     *
     * @throws AccessDeniedException | BadRequestException
     *
     * @return   Response object
     */
    public function getSkillsAndStatusCount(Request $request)
    {
        $params = [];
        $startDate = $this->getRequestParams($request, "start_date");
        $endDate = $this->getRequestParams($request, "end_date");

        if (($startDate && $endDate)) {
            if ((Carbon::createFromFormat("Y-m-d", $startDate)->lt(Carbon::createFromFormat("Y-m-d", $endDate)))
            ) {
                $params["startDate"] = Carbon::createFromFormat("Y-m-d", $startDate);
                $params["endDate"] = Carbon::createFromFormat("Y-m-d", $endDate);
            } else {
                throw new BadRequestException("start_date cannot be greater than end_date.");
            }
        }

        if ($location = $this->getRequestParams($request, "location")) {
            $params["location"] = $location;
        }

        $mentorshipRequests = MentorshipRequest::buildQuery($params)
            ->get();

        // Build the response object
        $response = $this->getEachSkillStatusCount($mentorshipRequests);

        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Compute the frequency of occurrence for skill statuses
     *
     * @param array $mentorshipRequests array of requests made for that period and location
     *
     * @return array of skills and the number of occurrences of skill status
     */
    private function getEachSkillStatusCount($mentorshipRequests)
    {
        $requestSkillWithStatus = [];
        $eachSkillWithStatusCount = [];

        foreach ($mentorshipRequests as $mentorshipRequest) {
            $status = $mentorshipRequest->status->name;
            
            foreach ($mentorshipRequest->requestSkills as $skill) {
                $requestSkillWithStatus[$skill->skill->name][] = $status;
            }
        }

        foreach ($requestSkillWithStatus as $skill => $skillStatusList) {
            $countMatchingStatus = array_count_values($skillStatusList);
            $eachSkillWithStatusCount[] = ["name" => $skill, "count" => $countMatchingStatus];
        }

        return $eachSkillWithStatusCount;
    }

    /**
     * It enables or disables a skill based on the status property set.
     *
     * @param integer $id - Unique ID of a particular skill.
     *
     * @throws BadRequestException | NotFoundException.
     *
     * @return object response of success or error message.
     */
    public function updateSkillStatus(Request $request, $id)
    {
        $status = $request->input('status');

        if ($status === null) {
            throw new BadRequestException("Invalid parameters.");
        }

        $skill = Skill::withTrashed()->find($id);

        if (!$skill) {
            throw new NotFoundException("Skill not found.");
        }

        if ($status === "active") {
            $skill->restore();
        } elseif ($status === "inactive") {
            $skill->delete();
        } else {
            throw new BadRequestException("Invalid parameters.");
        }

        return $this->respond(Response::HTTP_OK);
    }
}
