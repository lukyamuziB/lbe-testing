<?php

namespace App\Utility;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
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
     * @throws NotFoundException if there was an error sending the message
     */
    public function sendMessage($channel, $text)
    {
        $client = new Client();

        $slack_api_url = getenv("SLACK_API_URL");

        $response = $client->request('POST', $slack_api_url, [
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
            throw new NotFoundException('The Slack channel or user ' . $channel . ', was not found');
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
        $client = new Client();

        $slack_api_url = getenv("SLACK_API_URL");

        $requests = [];
        foreach ($channels as $channel) {
            $requests[] = $client->postAsync(
                $slack_api_url, ["form_params" => [
                    "token" => getenv("SLACK_TOKEN"),
                    "username" => "Lenken Notifications",
                    "as_user" => false,
                    "link_names" => true,
                    "icon_url" => getenv("SLACK_ICON"),
                    "channel" => $channel,
                    "text" => $message,
                ]]
            );
        }

        try {
            return Promise\unwrap($requests);
        } catch (ConnectException $connection_exception) {
            // TODO: Report slack error to lenken team
        }

    }
}
