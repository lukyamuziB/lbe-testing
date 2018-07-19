<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;
use App\Models\Request as MentorshipRequest;

class SuggestedSessionRescheduleRepository
{
    protected $redisClient;
    const RESCHEDULE_PENDING = 1;
    const RESCHEDULE_ACCEPTED = 2;
    const RESCHEDULE_REJECTED = 3;

    /**
     * SuggestedSessionRescheduleMock constructor
     */
    public function __construct()
    {
        $this->make();
    }

    /**
     * Instatiating Redis
     */
    public function make()
    {
        $this->redisClient = new Redis();
    }

    /**
     * Add suggested session reschedule to cache
     *
     * @param integer $requestId - unique request id
     * @param object $suggestedReschedule - suggested session reschedule
     *
     * @return void
     */
    public function add($requestId, $suggestedReschedule)
    {
        $suggestedSceduleChange = [
            "suggested_reschedule" => $suggestedReschedule,
            "status" => SuggestedSessionRescheduleRepository::RESCHEDULE_PENDING,
        ];

        $this->redisClient::set("request:$requestId:suggested-reschedule", json_encode($suggestedSceduleChange));
    }

    /**
     * Query for suggested changes to
     * session's schedule
     *
     * @param integer $requestId - Unique request id
     *
     * @return object $suggestedReschedule - suggested reschedule to a request
     */
    public function query($requestId)
    {
        $key = "request:$requestId:suggested-reschedule";

        if ($this->redisClient::exists($key)) {
            $suggestedReschedule = $this->redisClient::get($key);
            $status = json_decode($suggestedReschedule)->status;

            if ($status === SuggestedSessionRescheduleRepository::RESCHEDULE_PENDING) {
                return json_decode($suggestedReschedule)->suggested_reschedule;
            }
        }
    }

    /**
     * Update suggested reschedule status upon acceptance or rejection
     * of the suggested schedule
     *
     * @param integer $requestId - Unique request id
     * @param integer $status - Suggestion acceptance or rejection
     *
     * @return void
     */
    public function updateStatus($requestId, $status)
    {
        $key = "request:$requestId:suggested-reschedule";

        $suggestedReschedule = $this->redisClient::get($key);
        $schedule =  json_decode($suggestedReschedule)->suggested_reschedule;

        $suggestedSchedule = [
            "suggested_reschedule" => $suggestedReschedule,
            "status" => $status,
        ];
 
        $this->redisClient::set($key, json_encode($suggestedSchedule));

        return $schedule;
    }
}
