<?php
/**
 * Format request object to standardize API response
 *
 * @param MentorshipRequest $request - mentorship request to be formatted
 *
 * @return object - formatted request
 */
function formatRequestForAPIResponse($request)
{
    return (object)[
        "id" => $request->id,
        "created_by" => $request->created_by,
        "request_type_id" => $request->request_type_id,
        "title" => $request->title,
        "description" => $request->description,
        "status_id" => $request->status_id,
        "interested" => $request->interested,
        "match_date" => $request->match_date,
        "location" => $request->location,
        "duration" => (int)$request->duration,
        "pairing" => $request->pairing,
        "request_skills" => formatRequestSkills($request->requestSkills),
        "rating" => $request->rating ?? null,
        "created_at" => formatTime($request->created_at),
        "mentee" => (object)[
            "id" => $request->mentee->user_id ?? null,
            "email" => $request->mentee->email ?? "",
            "fullname" => $request->mentee->fullname ?? ""
        ],
        "mentor" => (object)[
            "id" => $request->mentor->user_id ?? null,
            "email" => $request->mentor->email ?? "",
            "fullname" => $request->mentor->fullname ?? ""
        ]
    ];
}

/**
 * Format multiple request objects to standardize API response
 *
 * @param array $requests - mentorship requests to be formatted
 *
 * @return array - array of formatted requests
 */
function formatMultipleRequestsForAPIResponse($requests)
{
    $formattedRequests = [];

    foreach ($requests as $request) {
        $formattedRequests[] = formatRequestForAPIResponse($request);
    }

    return $formattedRequests;
}

/**
 * Format Request Skills
 * Filter the result from skills table and add to the skills array
 *
 * @param array $requestSkills - the request skills
 *
 * @return array $skills
 */
function formatRequestSkills($requestSkills)
{
    $skills = [];
    foreach ($requestSkills as $skill) {
        $result = (object) [
            "id" => $skill->skill_id,
            "type" => $skill->type,
            "name" => $skill->skill->name
        ];
        $skills[] = $result;
    }

    return $skills;
}

/**
 * Format time
 * checks if the given time is null and
 * returns null else it returns the time in the date format
 *
 * @param string $time - the time in the date format
 *
 * @return mixed null|string
 */
function formatTime($time)
{
    return $time === null ? null : date("Y-m-d H:i:s", $time->getTimestamp());
}