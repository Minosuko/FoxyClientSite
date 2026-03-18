<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

$input = get_json_input();

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    send_error("ForbiddenOperationException", "Invalid credentials.");
}

$username = $input['username'];
$password = $input['password'];
$clientToken = isset($input['clientToken']) ? $input['clientToken'] : generate_uuid();
$requestUser = isset($input['requestUser']) ? $input['requestUser'] : false;

// Authenticate user
$stmt = $mysqli->prepare("SELECT id, password_hash, email, totp_enabled, totp_secret FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_error("ForbiddenOperationException", "Invalid credentials.");
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password_hash'])) {
    send_error("ForbiddenOperationException", "Invalid credentials.");
}

// 2FA Check
if ($user['totp_enabled']) {
    $totpCode = isset($input['totpCode']) ? $input['totpCode'] : null;
    if (!$totpCode) {
        send_error("TwoFactorRequired", "Two-factor authentication is required.");
    }
    if (!verify_totp($user['totp_secret'], $totpCode)) {
        send_error("TwoFactorRequired", "Invalid two-factor authentication code.");
    }
}

// Get profile
$stmt = $mysqli->prepare("SELECT uuid, name FROM profiles WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user['id']);
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

// Generate accessToken (JWT)
$token_id = generate_token();
$payload = [
    "sub" => str_replace('-', '', $selectedProfile['id']), // profileId as sub
    "foxyclient" => true,
    "username" => $user['username'],
    "uuid" => $selectedProfile['id'],
    "iat" => time(),
    "exp" => time() + (7 * 24 * 60 * 60), // 7 days
    "jti" => $token_id
];
$accessToken = generate_jwt($payload);
$expiresAt = date('Y-m-d H:i:s', $payload['exp']);

// Save token
$stmt = $mysqli->prepare("INSERT INTO tokens (user_id, access_token, client_token, expires_at) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user['id'], $accessToken, $clientToken, $expiresAt);
$stmt->execute();

$response = [
    "accessToken" => $accessToken,
    "clientToken" => $clientToken,
    "availableProfiles" => $availableProfiles
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
