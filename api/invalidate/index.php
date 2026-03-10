<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

$input = get_json_input();

if (!$input || !isset($input['accessToken']) || !isset($input['clientToken'])) {
    http_response_code(400);
    exit;
}

$accessToken = $input['accessToken'];
$clientToken = $input['clientToken'];

// Verify JWT structure and signature
if (!verify_jwt($accessToken)) {
    http_response_code(403);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM tokens WHERE access_token = ? AND client_token = ?");
$stmt->bind_param("ss", $accessToken, $clientToken);
$stmt->execute();

http_response_code(204);
?>
