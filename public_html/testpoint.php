<?php

require '../lib/discord-webhook.php';

header('Content-Type: application/json;charset=utf-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$url = getenv('DISCORD_URL');

if (!$url) {
    http_response_code(500);
    exit(1);
}

$webhook = new Webhook($url, '');

echo json_encode($webhook->test());
