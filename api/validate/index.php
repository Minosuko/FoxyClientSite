<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

$input = get_json_input();

if (!$input || !isset($input['accessToken'])) {
    http_response_code(400);
    exit;
}

$accessToken = $input['accessToken'];
$clientToken = isset($input['clientToken']) ? $input['clientToken'] : null;

// Verify JWT structure and signature
if (!verify_jwt($accessToken)) {
    http_response_code(403);
    exit;
}

$stmt = $mysqli->prepare("SELECT client_token FROM tokens WHERE access_token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $accessToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    exit;
}

$tokenData = $result->fetch_assoc();

if ($clientToken && $clientToken !== $tokenData['client_token']) {
    http_response_code(403);
    exit;
}

http_response_code(204); // No Content
?>
