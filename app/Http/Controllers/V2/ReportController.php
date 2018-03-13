<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\AccessDeniedException;
use App\Models\Status;
use App\Models\Request as MentorshipRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use App\Exceptions\BadRequestException;

class ReportController extends Controller
{
    use RESTActions;
    /**
     * Gets statistics of mentorship requests based on status
     *
     * @param Request $request - HttpRequest object
     *
     * @throws AccessDeniedException
     *
     * @return object - request statuses statistics
     */
    public function getRequestsStatusStatistics(Request $request)
    {
        $params = [];

        if ($location = $this->getRequestParams($request, "locations")) {
            $params["locations"] = explode(",", $location);
        }

        if ($startDate = $this->getRequestParams($request, "start_date")) {
            $params["startDate"] = Carbon::createFromFormat("d-m-Y", $startDate);
        }

        if ($endDate = $this->getRequestParams($request, "end_date")) {
            $params["endDate"] = Carbon::createFromFormat("d-m-Y", $endDate);
        }

        $mentorshipRequests = MentorshipRequest::buildPoolFilterQuery($params)->get();

        $response["total"] = count($mentorshipRequests);

        $requestsCount = $this->getRequestStatusCount($mentorshipRequests);

        $response["matched"] = $requestsCount["matched"];
        $response["completed"] = $requestsCount["completed"];
        $response["open"] = $requestsCount["open"];
        $response["cancelled"] = $requestsCount["cancelled"];

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
