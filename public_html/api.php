<?php

require '../lib/scores.php';
require '../lib/discord-webhook.php';

header('Content-Type: application/json;charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


/**
 * Checks the scores and returns the parsed
 * markdown and the username in associative array
 * @param string $data Raw JSON string data of the POST request
 * @return array Tuple containing 2 strings, `[0]` being the 
 * parsed markdown and `[1]` the username
 * @throws Exception If no JSON data or contains invalid scores
 */
function check_scores(string $data): ?array
{
    $parsed_data = json_decode($data, true);

    if (!$parsed_data) {
        throw new Exception('No valid JSON data provided');
    }

    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $count = (int) getenv('SCORE_COUNT') ?? '0';

    $scores = new Scores($parsed_data, $user_agent);

    $scores->valid($count);

    return [$scores->to_code(), $scores->name];
}

/**
 * Logic for handling the POST request and the
 * submission of the scores
 * @return bool Result sucessfully submitted to Discord webhook
 * @throws Exception If HTTP method is invalid 
 * or scores missing/invalid
 */
function handle_request(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid HTTP method');
    }

    $body = file_get_contents('php://input');
    [$scores, $username] = check_scores($body);

    $url = getenv('DISCORD_URL');
    $pfp = getenv('DISCORD_PFP');

    if (!$url) {
        throw new Exception('Missing Webhook URL');
    }

    if (!$pfp) {
        $pfp = '';
    }

    $webhook = new Webhook($url, $pfp);

    $postname = 'PCBValues - ' . $username;

    return $webhook->post($scores, $postname);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Allow: POST, OPTIONS');
    exit(0);
}

try {
    $res = handle_request();
    if ($res) {
        echo '{"success": true}';
    } else {
        throw new Exception('Unable to send data to Discord');
    }
} catch (Exception $e) {
    http_response_code(500);
    $data = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    echo json_encode($data);
}
