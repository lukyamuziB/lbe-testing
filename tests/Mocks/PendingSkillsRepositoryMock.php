<?php

namespace Test\Mocks;

use App\Repositories\PendingSkillsRepository;
use Carbon\Carbon;

class PendingSkillsRepositoryMock extends PendingSkillsRepository
{
    public $database;
    public $users;
    public $skills;
    /**
     * Firebase constructor mock
     */
    public function make()
    {
        $this->database = [
        "skills" => [
            "Ruby" =>(object) [
                "dateRequested" => "Jul 16, 2018",
                "skill" => "Ruby"
            ],
            "Python" =>(object) [
                "dateRequested" => "Jul 16, 2018",
                "skill" => "Python"
            ],
            "CSS" => (object) [
                "dateRequested" => "Jul 16, 2018",
                "skill" => "CSS"
            ],
            "React" => (object) [
                "dateRequested" => "Jul 16, 2018",
                "skill" => "React",
            ]
        ],
        "users" => [
            "-KesEogCwjq6lkOzKmLI"=> (object) [
                "dateRequested" => "Jul 16, 2018",
                "skill" => "Ruby",
                "status" => "pending"
            ],
            "-L4g35ttuyfK5kpzyocv" => (object) [
                "dateRequested" => "Jul 16, 2018",
                "skill" => "CSS",
                "status" => "pending"
            ],
        ]
        ];
    }

    /**
     * Adds a pending skill to the database and then associates that skill with a user
     *
     * @param string $skillName $userId
     *
     * @return object Details of the added skill and the user associated with it
     */
    public function add($skillName, $userId)
    {
        $existingSkills = array_keys($this->database["skills"]);

        if (in_array($skillName, $existingSkills)) {
            return($this->addSkillToUser($skillName, $userId));
        }

        $this->skills[$skillName] = [
            "dateRequested"=> Carbon::now()->toFormattedDateString(),
            "skill" => $skillName
        ];

        $this->users[$userId] = [
            "userId"=>$userId,
            "dateRequested"=> Carbon::now()->toFormattedDateString(),
            "skill" => $skillName,
            "status" => "pending"
        ];

        return $this->users[$userId];
    }

    /**
     * Associates a an existing pending skill with a user
     *
     * @param string $skill
     *
     * @param string $userId
     *
     * @return object Details of the added skill and the user associated with it
     */
    public function addSkillToUser($skill, $userId)
    {
        $this->database["users"][$userId] = [
            "userId"=>$userId,
            "dateRequested"=> Carbon::now()->toFormattedDateString(),
            "skill" => $skill,
            "status" => "pending"
        ];

        return $this->database["users"][$userId];
    }

    /**
     * Get all pending skills
     *
     *
     * @return object All the pending skills
     */
    public function getAll()
    {
        $pendingSkillsDetails = [];

        $skills = array_keys($this->database["skills"]);

        foreach ($skills as $skill) {
            $singleSkill = [];

            $skillDetail = $this->getById($skill);

            $singleSkill["associatedUsers"] = $skillDetail;

            array_push($pendingSkillsDetails, $singleSkill);
        }
        return $pendingSkillsDetails;
    }

    /**
     * Get all users with pending skills
     *
     * @return object All the users with pending skills
     */
    public function getAllUsers()
    {
        return $this->database["users"];
    }

    /**
     * Return a skill and all the associated users
     *
     * @param string $skill
     *
     * @return array|object A skill and it's associated users
     */
    public function getById($skill)
    {
        $associatedUsers = [];

        $users = $this->getAllUsers();

        foreach (array_keys($users) as $user) {
            $userSkills = $this->getUserSkills($user);

            if ($skill === $userSkills->skill) {
                array_push($associatedUsers, $user);
            }
        }
        $associatedUsers["skill"] = $skill;

        return $associatedUsers;
    }

    /**
     * Gets the pending skills for s single user
     *
     * @param string $userId
     *
     * @return object A user and the skills they have requested
     */
    public function getUserSkills($userId)
    {
        return $this->database["users"][$userId];
    }
}
