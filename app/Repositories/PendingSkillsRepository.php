<?php

namespace App\Repositories;

use Carbon\Carbon;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use App\Interfaces\RepositoryInterface;

class PendingSkillsRepository implements RepositoryInterface
{
    protected $database;

    public function __construct()
    {
        $this->make();
    }

    /**
     * Create a Firebase database instance
     */
    public function make()
    {
        $serviceAccount =
            ServiceAccount::fromJsonFile(__DIR__ . "/../../firebase-credentials.json");
        $databseUri = getenv("FIREBASE_DATABASE_URI");
       
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri($databseUri)
            ->create();

        $this->database = $firebase->getDatabase();
    }

    /**
     * Adds a pending skill to the database and then associates that skill with a user
     *
     * @param string $skillName - name of skill to be added
     * @param string $userId - name of associated user
     *
     * @return object - Details of the added skill and the user associated with it
     */
    public function add($skillName, $userId)
    {
        $skill = [
            "name" => $skillName,
            "dateRequested" => Carbon::now()->toFormattedDateString()
        ];

        $existingSkill = $this->getExistingSkill($skillName);

        if ($existingSkill) {
            return $this->addSkillToUser($existingSkill, $userId);
        } else {
            $updates = [
                "skills/$skillName" => $skill,
                "users/$userId/$skillName" => $skill,
            ];

            $this->database->getReference()->update($updates);

            $pendingSkill = $this->database->getReference("users/${userId}/${skillName}")
                                            ->getValue();

            $pendingSkill["userId"] = $userId;
            $pendingSkill["status"] = "pending";


            return (object)$pendingSkill;
        }
    }

    /**
     * Adds an existing pending skill to a user
     *
     * @param string $skillName - name of skill to be added
     * @param string $userId - name of associated user
     *
     * @return object - Details of the added skill and the user associated with it
     */
    public function addSkillToUser($skillName, $userId)
    {
        $skill = [
            "name" => $skillName,
            "dateRequested" => Carbon::now()->toFormattedDateString()
        ];

        $updates = [
            "users/$userId/$skillName" => $skill,
        ];

        $this->database->getReference()
                        ->update($updates);

        $pendingSkill = $this->database->getReference("users/${userId}/${skillName}")
                                        ->getValue();

        $pendingSkill["userId"] = $userId;
        $pendingSkill["status"] = "pending";

        return (object)($pendingSkill);
    }

    /**
     * Get all pending skills
     *
     * @return object All the pending skills
     */
    public function getAll()
    {
        $pendingSkillsDetails = [];

        $allPendingSkills = $this->database->getReference("skills")
                                            ->orderByKey()
                                            ->getValue();

        foreach ($allPendingSkills as $pendingSkill) {
            $singleSkill = [];

            $skillDetail = $this->getById($pendingSkill["name"]);

            $singleSkill["skill"] = $pendingSkill;
            $singleSkill["associatedUsers"] = $skillDetail;
            array_push($pendingSkillsDetails, $singleSkill);
        }

        return (object)$pendingSkillsDetails;
    }

    /**
     * Get all users with pending skills
     *
     * @return object All the users with pending skills
     */
    public function getAllUsers()
    {
        $allUsers = $this->database->getReference("users")
                                    ->orderByKey()
                                    ->getValue();

        return (object)$allUsers;
    }

    /**
     * Return a skill and all the associated users
     *
     * @param string $skill - skill whose associated users are returned
     *
     * @return object A skill and the details of all the associated users
     */
    public function getById($skill)
    {
        $associatedUsers = [];

        $pendingskill = $this->getExistingSkill($skill);

        $users = $this->database->getReference("users")
                                ->orderByKey()
                                ->getSnapShot()
                                ->getValue();

        $userKeys = array_keys($users);

        foreach ($userKeys as $key) {
            $userSkills = $this->database->getReference("users/${key}/${pendingskill}")
                                        ->orderByKey()
                                        ->getSnapShot()
                                        ->getValue();

            if ($userSkills) {
                $associatedUsers[$key] = $userSkills;
            }
        }


        return (object)$associatedUsers;
    }

    /**
     * Gets an existing skill from the database. It doesn't return a value if no skill exists.
     *
     * @param $skill - name of the skill to get
     *
     * @return mixed
     */
    private function getExistingSkill($skill)
    {
        $existingSkills = $this->database->getReference("skills")
                                        ->orderByChild("name")
                                        ->getSnapShot()
                                        ->getValue();

        foreach ($existingSkills as $existingSkill) {
            if (strcasecmp($existingSkill["name"], $skill) == 0) {
                return $existingSkill["name"];
            }
        }
    }

    /**
     * Gets the pending skills for s single user
     *
     * @param string $userId - userId whose pending skills will be returned
     *
     * @return object A user and the skills they have requested
     *
     */
    public function getUserSkills($userId)
    {
        $userSkills = $this->database->getReference("users/${userId}")
                                    ->orderByKey()
                                    ->getSnapShot()
                                    ->getValue();

        return (object)$userSkills;
    }

    public function query($param)
    {
    }
}
