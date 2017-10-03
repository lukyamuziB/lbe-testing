<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Request as MentorshipRequest;
use App\Clients\AISClient;
use App\Models\UserSkill;
use App\Models\Skill;
use App\Models\Session;
use App\Models\Rating;

class UserController extends Controller
{
    use RESTActions;
    private $aisClient;

    public function __construct(AISClient $aisClient)
    {
        $this->aisClient = $aisClient;
    }

    /**
     * Gets a user's infomation based on their user id.
     *
     * @return Response object
     */
    public function get($id)
    {
        $userDetails = $this->aisClient->getUserById($id);

        $userSkillObjects = UserSkill::with('skill')->where('user_id', $id)->get();

        $userSkills = [];

        foreach ($userSkillObjects as $skill) {
            $extractedSkills = (object) [
                "id" => $skill->skill_id,
                "name"=> $skill->skill->name
            ];
            array_push($userSkills, $extractedSkills);
        }

        $requestCount = $this->getMenteeRequests($id);

        $totalLoggedHours = Session::getTotalLoggedHours($id);

        $averageRating = Rating::getAverageRatings($id);
    
        $response = (object) [
            "id" => $userDetails["id"],
            "picture" => $userDetails["picture"],
            "first_name" => $userDetails["first_name"],
            "name" => $userDetails["name"],
            "location" => $userDetails["location"]["name"] ?? "",
            "cohort" => $userDetails["cohort"] ?? "",
            "roles" => $userDetails["roles"],
            "placement" => $userDetails["placement"] ?? "",
            "email" => $userDetails["email"],
            "level" => $userDetails["level"] ?? "",
            "skills" => $userSkills,
            "request_count" => $requestCount,
            "logged_hours" => $totalLoggedHours,
            "rating" => $averageRating
        ];
        
        return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Returns the requests for a particular mentee
     *
     * @param string $user_id
     * @return integer total number of mentorship request made
     */
    private function getMenteeRequests($userId)
    {
        return MentorshipRequest::where('mentee_id', $userId)
            ->count();
    }
}
