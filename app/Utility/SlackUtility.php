<?php

namespace App\Utility;

use App\Exceptions\UnauthorizedException;
use App\Repositories\SlackUsersRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise;
use App\Exceptions\NotFoundException;

class SlackUtility
{

    protected $client;
    protected $token;

    protected $slack_repository;

    /**
     * SlackUtility constructor.
     *
     * @param SlackUsersRepository $slack_repository Dependency Injection
     */
    public function __construct(SlackUsersRepository $slack_repository)
    {
        $this->slack_repository = $slack_repository;

        $this->client = new Client();
        $this->base_url = getenv("SLACK_API_URL");
        $this->token = getenv("SLACK_TOKEN");
    }

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
        $api_url = $this->base_url."/chat.postMessage";

        $response = $this->client->request('POST', $api_url, [
                    "form_params" => [
                        "token" => $this->token,
                        "username" => "Lenken Notifications",
                        "as_user" => false,
                        "link_names" => true,
                        "icon_url" => getenv("SLACK_ICON"),
                        "channel" => $channel,
                        "text" => $text,
                    ],
                    "verify" => false
            ]
        );

        $response = json_decode($response->getBody(), true);        

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
        $slack_api_url = $this->base_url."/chat.postMessage";

        $requests = [];
        foreach ($channels as $channel) {
            $requests[] = $this->client->postAsync(
                $slack_api_url, [
                    "form_params" => [
                        "token" => $this->token,
                        "username" => "Lenken Notifications",
                        "as_user" => false,
                        "link_names" => true,
                        "icon_url" => getenv("SLACK_ICON"),
                        "channel" => $channel,
                        "text" => $message,
                    ],
                    "verify" => false
                ]
            );
        }

        try {
            return Promise\unwrap($requests);
        } catch (ConnectException $connection_exception) {
            // TODO: Report slack error to lenken team
        }

    }

    /**
     * Verifies a slack handle with the user's email using the slack user repository
     *
     * @param string $slack_handle slack handle being entered
     * @param string $current_user_email email address of current user
     *
     * @return object the slack user
     *
     * @throws NotFoundException if the handle isn't found
     * @throws UnauthorizedException if the handle belongs to another user
     */
    public function verifyUserSlackHandle($slack_handle, $current_user_email)
    {
        $slack_user = $this->slack_repository->getByHandle($slack_handle);

        if (is_null($slack_user)) {
            throw new NotFoundException("slack handle not found");
        }

        if ($slack_user->email !== $current_user_email) {
            throw new UnauthorizedException("wrong slack handle");
        }

        return $slack_user;
    }

    /**
     * Gets all users in the slack team
     *
     * @return array containing all users in the slack team
     */
    public function getAllUsers()
    {
        $api_url = $this->base_url."/users.list";
        $response = $this->client->request(
            "POST", $api_url, [
                "form_params" => [
                    "token" => $this->token
                ],
                "verify" => false
            ]
        );

        $response = json_decode($response->getBody(), true);

        return $response["ok"] ? $response["members"] : [];
    }

}
