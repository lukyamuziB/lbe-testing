<?php

use App\Jobs\SendNotificationJob;
use App\Jobs\ValidateAndSendNotificationJob;

/**
 * Dispatch notification job
 *
 * @param String $recipient - Receiver of the mail
 * @param Object $payload - notification payload
 *
 * @return void
 */
function sendEmailNotification($recipient, $payload)
{
    return dispatch(new SendNotificationJob($recipient, $payload));
}

/**
 * Dispatch validate and send notification job
 *
 * @param String $recipient - Receiver of the mail
 * @param Object $payload - notification payload
 *
 * @return void
 */
function sendEmailNotificationBasedOnUserSettings($recipient, $payload)
{
    return dispatch(new ValidateAndSendNotificationJob($recipient, $payload));
}
