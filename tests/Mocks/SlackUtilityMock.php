<?php

namespace Test\Mocks;

use App\Exceptions\NotFoundException;
use App\Utility\SlackUtility;

/**
 * Class SlackUtilityMock
 * @package Test\Mocks
 */
class SlackUtilityMock extends SlackUtility
{
   /**
     * sendMessage sends a slack message to a specified user Id, slackhandle
     * or slack channel
     *
     * @param string $channel the slack user id, user handle or channel name
     * @param string $text the message to be sent
     * @return object containing the json response
     * @throws NotFoundException if there was an error sending the message
     */
    public function sendMessage($channel, $text)
    {
        $channels = ["@amao", "@bayo", "wrong"];

        $response = array_merge(
            array("ok" => true, "channel" => $channel),
            (in_array($channel, $channels) ? array("message" => ["text" => $text]) : array())
        );

        if (!isset($response["message"])) {
            throw new NotFoundException("The Slack channel or user $channel, was not found");
        }

        return $response;
    }

    /**
     * sendMessageToMultipleChannels - send a slack message to
     * multiple slack channels
     * @param $channels - list of channels to send message to
     * @param $message - message to send to multiple channels
     * @internal param string $messages_information - array of objects containing message text and
     * channel to send to in each object
     * @return array
     */
    public function sendMessageToMultipleChannels($channels, $message)
    {
        return ["message" => "mutiple channel sent"];
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
                ["id" => "C1AOPLE39"]
            ]
        ];

        return $response["ok"] ? $response["members"] : [];
    }
}
