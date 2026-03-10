<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

$input = get_json_input();

if (!$input || !isset($input['accessToken'])) {
    send_error("ForbiddenOperationException", "Invalid token.");
}

$accessToken = $input['accessToken'];
$clientToken = isset($input['clientToken']) ? $input['clientToken'] : null;
$requestUser = isset($input['requestUser']) ? $input['requestUser'] : false;

// Find token
$stmt = $mysqli->prepare("SELECT user_id, client_token FROM tokens WHERE access_token = ?");
$stmt->bind_param("s", $accessToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_error("ForbiddenOperationException", "Invalid token.");
}

$tokenData = $result->fetch_assoc();

if ($clientToken && $clientToken !== $tokenData['client_token']) {
    send_error("ForbiddenOperationException", "Invalid token.");
}

// Get user and profile
$userId = $tokenData['user_id'];
$stmt = $mysqli->prepare("SELECT id, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$stmt = $mysqli->prepare("SELECT uuid, name FROM profiles WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profResult = $stmt->get_result();

$availableProfiles = [];
$selectedProfile = null;

if ($profResult->num_rows > 0) {
    $profile = $profResult->fetch_assoc();
    $p = [
        "id" => str_replace('-', '', $profile['uuid']),
        "name" => $profile['name']
    ];
    $availableProfiles[] = $p;
    $selectedProfile = $p;
}

// Generate new accessToken (JWT)
$token_id = generate_token();
$payload = [
    "sub" => str_replace('-', '', $selectedProfile['id']),
    "iat" => time(),
    "exp" => time() + (7 * 24 * 60 * 60),
    "jti" => $token_id
];
$newAccessToken = generate_jwt($payload);
$expiresAt = date('Y-m-d H:i:s', $payload['exp']);

// Update token
$stmt = $mysqli->prepare("UPDATE tokens SET access_token = ?, expires_at = ? WHERE access_token = ?");
$stmt->bind_param("sss", $newAccessToken, $expiresAt, $accessToken);
$stmt->execute();

$response = [
    "accessToken" => $newAccessToken,
    "clientToken" => $tokenData['client_token']
];

if ($selectedProfile) {
    $response["selectedProfile"] = $selectedProfile;
}

if ($requestUser) {
    $response["user"] = [
        "id" => (string)$user['id'],
        "properties" => []
    ];
}

send_json_response($response);
?>
