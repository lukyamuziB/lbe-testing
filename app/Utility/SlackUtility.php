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
     * sendMessage - send a slack message to
     * one or more slack channels
     * @param array $recipients - list of channels to send message to
     * @param string $message - message to send to multiple channels
     * @param string $attachments optional attachments to send
     * @internal param string $messages_information - array of objects containing message text and
     * channel to send to in each object
     * @return array
     */
    public function sendMessage($recipients, $message, $attachments = "")
    {
        $slack_api_url = $this->base_url."/chat.postMessage";

        $requests = [];
        foreach ($recipients as $recipient) {
            $requests[] = $this->client->postAsync(
                $slack_api_url, [
                    "form_params" => [
                        "token" => $this->token,
                        "username" => "Lenken Notifications",
                        "as_user" => false,
                        "link_names" => true,
                        "icon_url" => getenv("SLACK_ICON"),
                        "channel" => $recipient,
                        "text" => $message,
                        "attachments" => $attachments
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
