<?php

namespace Test\Mocks;

use App\Exceptions\NotFoundException;
use App\Utility\SlackUtility;

/**
 * Class SlackUtilityMock
 *
 * @package Test\Mocks
 */
class SlackUtilityMock extends SlackUtility
{
    /**
     * sendMessageToMultipleChannels - send a slack message to
     * multiple slack channels
     *
     * @param    $recipients - list of channels to send message to
     * @param    $message - message to send to multiple channels
     * @internal param string $messages_information - array of objects containing message text and
     * channel to send to in each object
     * @return   array
     */
    public function sendMessage($recipients, $message, $attachments = "")
    {
        $valid_recipients = ["@amao", "@bayo", "wrong"];

        if (count($recipients) > 1) {
            $response
                = $attachments ? ["message" => "mutiple channel sent with attachments"] :
                ["message" => "mutiple channel sent"];
        } else {
            $response = array_merge(
                array("ok" => true, "channel" => $recipients[0]),
                (in_array($recipients[0], $valid_recipients) ?
                array("message" => ["text" => $message]) :
                array("error" => "The Slack channel or user $recipients[0], was not found"))
            );
        }

        return $response;
    }

    /**
     * Gets all users in the slack team
     *
     * @return array containing all users in the slack team
     */
    public function getAllUsers()
    {
        $response = [
            "ok" => true,
            "members"=> [
                ["id" => "C1AOPLE39",
                "real_name" => "slackbot",
                "name" => "slackbot",
                "color"=> "4d5e26",
                "profile" =>
                    ["email" => "slackbot@andela.com"]
                ],
                ["id" => "U02UJLWUQ",
                "real_name" => "sombor",
                "name" => "sombo",
                "color"=> "4d8e20",
                "profile" =>
                    ["email" => "sombor@andela.com"]
                ]
            ]
        ];

        return $response["ok"] ? $response["members"] : [];
    }
}
