<?php

use App\Jobs\SendNotificationJob;

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
