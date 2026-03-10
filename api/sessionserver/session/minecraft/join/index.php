<?php
require_once __DIR__ . '/../../../../../includes/db.php';
require_once __DIR__ . '/../../../../../includes/yggdrasil.php';

$input = get_json_input();

if (!$input || !isset($input['accessToken']) || !isset($input['selectedProfile']) || !isset($input['serverId'])) {
    http_response_code(400);
    send_error("IllegalArgumentException", "Access token, selected profile, and server ID are required.");
    exit;
}

$accessToken = $input['accessToken'];
$selectedProfile = $input['selectedProfile'];
$serverId = $input['serverId'];

// Validate token and profile
$stmt = $mysqli->prepare("SELECT u.id, p.uuid FROM users u JOIN tokens t ON u.id = t.user_id JOIN profiles p ON u.id = p.user_id WHERE t.access_token = ? AND REPLACE(p.uuid, '-', '') = ? AND t.expires_at > NOW()");
$stmt->bind_param("ss", $accessToken, $selectedProfile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    send_error("ForbiddenOperationException", "Invalid token or profile.");
    exit;
}

$data = $result->fetch_assoc();
$uuid = $data['uuid'];
$ip = $_SERVER['REMOTE_ADDR'];

// Clear old sessions for this user to prevent clutter
$stmt = $mysqli->prepare("DELETE FROM sessions WHERE uuid = ?");
$stmt->bind_param("s", $uuid);
$stmt->execute();

// Create new session. Replace is robust against collisions since server_id is the primary key.
$stmt = $mysqli->prepare("REPLACE INTO sessions (uuid, server_id, ip) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $uuid, $serverId, $ip);
$stmt->execute();

http_response_code(204);
?>
