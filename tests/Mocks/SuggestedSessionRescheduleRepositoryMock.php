<?php

namespace Test\Mocks;
use App\Repositories\SuggestedSessionRescheduleRepository;

/**
 * Class SuggestedSessionRescheduleMock
 *
 * @package Test\Mocks
 */
class SuggestedSessionRescheduleRepositoryMock extends SuggestedSessionRescheduleRepository
{
    private $dataStore = [];
    const RESCHEDULE_PENDING = 1;
    const RESCHEDULE_ACCEPTED = 2;
    const RESCHEDULE_REJECTED = 3;

    public function __construct()
    {
        $this->make();
    }

    /**
     * Populate redis client with a mock of the data cached
     */
    public function make()
    {
        $this->dataStore = [
            "request:22:suggested-reschedule" => json_encode([
                "suggested_reschedule" => [
                    "pairing" => [
                        "start_time" =>
                            "00:30", "end_time" => "02:00", "days" => ["monday", "tuesday"], "timezone" => "EAT"
                    ]
                ],
                "status" => 1
            ])
        ];
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
            "status" => SuggestedSessionRescheduleRepositoryMock::RESCHEDULE_PENDING,
        ];

        $this->dataStore["request:$requestId:suggested-reschedule"] = json_encode($suggestedSceduleChange);
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

        if (array_key_exists($key, $this->dataStore)) {
            $suggestedReschedule = $this->dataStore[$key];
            $status = json_decode($suggestedReschedule)->status;

            if ($status === SuggestedSessionRescheduleRepositoryMock::RESCHEDULE_PENDING) {
                return json_decode($suggestedReschedule);
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

        $suggestedReschedule = $this->dataStore[$key];
        $schedule =  json_decode($suggestedReschedule)->suggested_reschedule;

        $suggestedSchedule = [
            "suggested_reschedule" => $suggestedReschedule,
            "status" => $status,
        ];
 
        $this->dataStore[$key] = json_encode($suggestedSchedule);

        return $schedule;
    }
}
