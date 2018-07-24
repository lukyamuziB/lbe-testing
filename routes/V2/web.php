<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */
$router->get("/", "LandingPageController@get");

/**
 * Routes for  requests
 */
$router->group(["prefix" => "api/v2/requests"], function ($router) {
    $router->get("pool", "RequestController@getRequestsPool");
    $router->get("/", "RequestController@getAllRequests");
    $router->get("history", "RequestController@getUserHistory");
    $router->get("in-progress", "RequestController@getRequestsInProgress");
    $router->get("pending", "RequestController@getPendingPool");
    $router->put("{id}", "RequestController@editRequest");
    $router->get('search', "RequestController@searchRequests");
    $router->patch("{id}/cancel-request", "RequestController@cancelRequest");
    $router->patch("{id}/update-status", "RequestController@updateStatus");
    $router->patch("{id}/withdraw-interest", "RequestController@withdrawInterest");
    $router->patch("{id}/indicate-interest", "RequestController@indicateInterest");
    $router->get("{id}/sessions", "SessionController@getRequestSessions");
    $router->patch("{id}/accept-user", "RequestController@acceptInterestedUser");
    $router->patch("{id}/reject-user", "RequestController@rejectInterestedUser");
    $router->group(["middleware" => "admin"], function ($router) {
        $router->get("status-statistics", "ReportController@getRequestsStatusStatistics");
    });
    $router->get("{id}", "RequestController@getRequest");
    $router->post("", "RequestController@createRequest");
    $router->post("{id}/suggest-reschedule", "RequestController@addSuggestedReschedule");
    $router->get("{id}/suggested-reschedule", "RequestController@getSuggestedSessionReschedule");
    $router->patch("{id}/reschedule-status", "RequestController@acceptOrRejectReschedule");
});
/**
 * Routes for skills
 */
$router->group(["prefix" => "api/v2/skills"], function ($router) {
    $router->get("request-skills", "SkillController@getSkillsWithRequests");
    $router->get("/", "SkillController@getSkills");
    $router->get("{skillId}/requests", "RequestController@getSkillRequests");
    $router->get("{skillId}/mentors", "SkillController@getSkillMentors");
    $router->group(["middleware" => "admin"], function ($router) {
        $router->get('status-report', 'SkillController@getSkillsAndStatusCount');
        $router->get("{skillId}/requests", "RequestController@getSkillRequests");
        $router->patch("{skillId}/update-status", "SkillController@updateSkillStatus");
        $router->post("", 'SkillController@addSkill');
    });
});
/**
 * Routes for users
 */
$router->group(["prefix" => "api/v2/users"], function ($router) {
    $router->get("/", "UserController@getUsersByIds");
    $router->get("/search", "UserController@search");
    $router->get("/{userId}/comments", "UserController@getComments");
    $router->get("/{userId}/statistics", "UserController@getStatistics");
    $router->get("/{userId}/skills", "UserController@getSkills");
    $router->get("{userId}/rating", "UserController@getRating");
    $router->get("{userId}", "UserController@get");
    $router->post("{userId}/skills", "UserController@addUserSkill");
    $router->delete("{userId}/skills/{skillId}", "UserController@deleteUserSkill");
});

/**
 * Routes for sessions
 */
$router->group(["prefix" => "api/v2/sessions"], function ($router) {
    $router->post("{id}/files", "SessionController@uploadSessionFile");
    $router->delete("{id}/files/{fileId}", "SessionController@deleteSessionFile");
    $router->patch("{id}/attach", "SessionController@attachSessionFile");
    $router->patch("{id}/detach", "SessionController@detachSessionFile");
    $router->patch("{id}/confirm", "SessionController@confirmSession");
    $router->patch("{id}/reject", "SessionController@rejectSession");
    $router->get("{id}", "SessionController@getSingleSession");
});

/**
 * Routes for request sessions
 */
$router->group(["prefix" => "api/v2/requests"], function ($router) {
    $router->get("{id}/sessions/dates", "SessionController@getSessionDates");
    $router->patch("{id}/sessions/{sessionId}", "SessionController@updateSession");
    $router->post("{id}/sessions", "SessionController@createSession");
});

/**
 * Routes for files manipulation
 */
$router->group(["prefix" => "api/v2/files"], function ($router) {
    $router->get("{id}", "FilesController@downloadFile");
    $router->post("/", "FilesController@uploadFile");
    $router->delete("{id}", "FilesController@deleteFile");
});

/**
 * Routes for notifications
 */
$router->group(["prefix" => "api/v2/notifications"], function ($router) {
    $router->get("request-withdrawn/{requestId}", "NotificationController@getInterestedUsers");
    $router->get("request-matches-skills/{requestId}", "NotificationController@getUsersWithMatchingRequestSkills");
    $router->group(["middleware" => "admin"], function ($router) {
        $router->get("/", "NotificationController@all");
        $router->post("/", "NotificationController@add");
        $router->put("/{id}", "NotificationController@put");
        $router->delete("/{id}", "NotificationController@delete");
    });
});

/**
 * Routes for user notification settings
 */
$router->group(["prefix" => "api/v2/user"], function ($router) {
    $router->get("{userId}/notifications/{notificationId}", "NotificationController@getUserNotificationSettings");
    $router->put("{userId}/notifications/{notificationId}", "NotificationController@updateUserSettings");
    $router->get("{userId}/notifications", "NotificationController@getUserSettings");
});
