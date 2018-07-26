<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\Exception;
use Illuminate\Http\Response;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;
use Illuminate\Http\Request;
use App\Repositories\PendingSkillsRepository;

/**
 * Class SkillController
 *
 * @package App\Http\Controllers\V2
 */
class PendingSkillsController extends Controller
{
    use RESTActions;
    private $pendingSkillsRepository;

    public function __construct(
        PendingSkillsRepository $pendingSkillsRepository
    ) {
        $this->pendingSkillsRepository = $pendingSkillsRepository;
    }

    /**
     * Adds a pending skill to the database
     *
     * @param Request $request - HTTPRequest object
     *
     * @throws BadRequestException
     *
     * @return JSON - Details of the added skill and the user associated with it
     */
    public function addPendingSkill(Request $request)
    {
        $skill = $request->skill;
        $userId = $request->userId;

        if (!$userId && !$skill) {
            throw new BadRequestException("Invalid parameters.");
        }

        return $this->respond(Response::HTTP_CREATED, $this->pendingSkillsRepository->add($skill, $userId));
    }

    /**
     * Get all pending skills
     *
     * @return JSON - Array of all the pending skills
     */
    public function getAllPendingSkills()
    {
        $pendingSkills = $this->pendingSkillsRepository->getAll();

        return $this->respond(Response::HTTP_OK, $pendingSkills);
    }

    /**
     * Get all users with pending skills
     *
     * @return JSON - Array of all Users with pending skills
     */
    public function getAllUsers()
    {
        $users = $this->pendingSkillsRepository->getAllUsers();

        return $this->respond(Response::HTTP_OK, $users);
    }

    /**
     * Return a skill and all the associated users
     *
     * @param string $skill - the name of the skill
     *
     * @throws BadRequestException|NotFoundException
     *
     * @return JSON - A skill and the details of all the associated users
     */
    public function getPendingSkillDetails($skill)
    {
        $decodedSkill = urldecode($skill);

        if (!$decodedSkill) {
            throw new BadRequestException("Invalid parameter");
        }

        return $this->respond(Response::HTTP_OK, $this->pendingSkillsRepository->getById($decodedSkill));
    }

    /**
     * Gets a user's pending skills
     *
     * @param string $userId - unique ID of the user
     *
     * @throws BadRequestException
     *
     * @return JSON - A user and the skills they have requested
     */
    public function getUserSkills($userId)
    {
        if (!$userId) {
            throw new BadRequestException("Invalid parameter");
        }

        $userSkills = $this->pendingSkillsRepository->getUserSkills($userId);

        return ($this->respond(Response::HTTP_OK, $userSkills));
    }
}
