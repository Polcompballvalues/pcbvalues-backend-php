<?php
/**
 * Class representing a Discord Webhook.
 */
class Webhook
{
    /** Webhook URL */
    private string $url;
    /** URL of the user avatar to display on the webhook */
    private string $pfp;

    /**
     * Constructs a new instance of the class
     * @param string $url Webhook URL
     * @param string $pfp URL of the user avatar to display
     */
    function __construct(string $url, string $pfp)
    {
        $this->url = $url;
        $this->pfp = $pfp;
    }

    /**
     * Posts data to webhook
     * @param string $text Text body to send to Webhook
     * @param string $username Username to display on the Message
     */
    function post(string $text, string $username): bool
    {
        $data = [
            'content' =>  $text,
            'username' => $username,
            'avatar_url' => $this->pfp
        ];

        $options = [
            'http' => [
                'header' => 'Content-Type: application/json;charset=utf-8',
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $ctx  = stream_context_create($options);

        $_result = file_get_contents($this->url, false, $ctx);

        $resp_code = (int) explode(' ', $http_response_header[0])[1];

        return $resp_code < 300;
    }

    /**
     * Performs an HTTP HEAD request to the webhook URL
     * @return array Associative array containing the in `code` key 
     * representing the response status code and the `text` key
     * containing the response text
     */
    function test(): array
    {
        $options = [
            'http' => [
                'method' => 'HEAD'
            ]
        ];

        $ctx  = stream_context_create($options);

        $result = file_get_contents($this->url, false, $ctx);

        return [
            'code' => (int) explode(' ', $http_response_header[0])[1],
            'text' => $result ?? ''
        ];
    }
}
