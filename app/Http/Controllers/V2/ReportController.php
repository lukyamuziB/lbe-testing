<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\AccessDeniedException;
use App\Models\Status;
use App\Models\Request as MentorshipRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    use RESTActions;

    /**
     * Gets statistics of mentorship requests based on status
     *
     * @param object $mentorshipRequests - all requests
     * @throws AccessDeniedException
     *
     * @return object - request statuses statistics
     */
    public function getRequestsStatusStatistics(Request $request)
    {
            if ($request->user()->role !== "Admin") {
                throw new AccessDeniedException("you do not have permission to perform this action");
            }

            $params = [];

            if ($location = $this->getRequestParams($request, "locations")) {
                $params["locations"] = explode(",", $location);
            }

            $mentorshipRequests = MentorshipRequest::buildPoolFilterQuery($params)
                ->get();

            $response["totalRequests"] = count($mentorshipRequests);

            $requestsCount = $this->getRequestStatusCount($mentorshipRequests);

            $response["totalMatchedRequests"] = $requestsCount["matched"];
            $response["totalCompletedRequests"] = $requestsCount["completed"];
            $response["totalOpenRequests"] = $requestsCount["open"];
            $response["totalCancelledRequests"] = $requestsCount["cancelled"];

            return $this->respond(Response::HTTP_OK, $response);
    }

    /**
     * Creates an object that contains statuses statistics
     *
     * @param object $mentorshipRequests - all requests
     *
     * @return object - request statuses and their statistics
     */
    private function getRequestStatusCount($mentorshipRequests)
    {
        $requestStatusCount["open"] = 0;
        $requestStatusCount["completed"] = 0;
        $requestStatusCount["cancelled"] = 0;
        $requestStatusCount["matched"] = 0;
 
        foreach ($mentorshipRequests as $mentorshipRequest) {
            if ($mentorshipRequest->status_id == Status::OPEN) {
                $requestStatusCount["open"]++;
            }
            if ($mentorshipRequest->status_id == Status::COMPLETED) {
                $requestStatusCount["completed"]++;
            }
            if ($mentorshipRequest->status_id == Status::CANCELLED) {
                $requestStatusCount["cancelled"]++;
            }
            if ($mentorshipRequest->status_id == Status::MATCHED) {
                $requestStatusCount["matched"]++;
            }
        }
        return $requestStatusCount;
    }
}
