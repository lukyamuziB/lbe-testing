<?php

namespace App\Utility;

use GuzzleHttp\Client;
use App\Exceptions\NotFoundException;

class SlackUtility
{
    /**
     * sendMessage sends a slack message to a specified user Id, slackhandle
     * or slack channel
     *
     * @param string $channel the slack user id, user handle or channel name
     * @param string $text the message to be sent
     * @return object containing the json response
     */
    public function sendMessage($channel, $text)
    {
        $client = new Client();

        $api_url = getenv("SLACK_API_URL");

        $response = $client->request('POST', $api_url, [
            "form_params" => [
                "token" => getenv("SLACK_TOKEN"),
                "username" => "Lenken Notifications",
                "as_user" => false,
                "link_names" => true,
                "icon_url" => getenv("SLACK_ICON"),
                "channel" => $channel,
                "text" => $text,
            ],
            "verify" => false
        ]);

        $response = json_decode($response->getBody(), true);

        if (!isset($response["message"])) {
            throw new NotFoundException('The Slack channel or user '.$channel.', was not found');
        }

        return $response;
    }
}
