<?php
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/oauth_helper.php';

// 1. Get Bearer Token
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (empty($auth_header) || strpos($auth_header, 'Bearer ') !== 0) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'unauthorized', 'error_description' => 'Bearer token required.']);
    exit;
}

$token = substr($auth_header, 7);

// 2. Validate Token
require_once __DIR__ . '/../../../../includes/yggdrasil.php';
$payload = verify_jwt($token);

$user_id = null;

if ($payload && isset($payload['username'])) {
    // Valid JWT, fetch user by username
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $payload['username']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u) {
        $user_id = $u['id'];
    }
} else {
    // Fallback: check database for legacy opaque token
    $stmt = $mysqli->prepare("SELECT * FROM oauth_access_tokens WHERE access_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $token_data = $stmt->get_result()->fetch_assoc();

    if ($token_data && strtotime($token_data['expires']) >= time()) {
        $user_id = $token_data['user_id'];
    }
}

if (!$user_id) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'The access token is invalid or expired.']);
    exit;
}

// 3. Fetch User Info
$stmt = $mysqli->prepare("
    SELECT u.id, u.username, u.email, p.uuid, p.name as profile_name
    FROM users u
    LEFT JOIN profiles p ON u.id = p.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'user_not_found']);
    exit;
}

// 4. Return Response
header('Content-Type: application/json');
echo json_encode([
    'username' => $user['username'],
    'email' => $user['email'],
    'uuid' => $user['uuid'] ?? "",
    'profile_name' => $user['profile_name'] ?? $user['username'],
    'id' => $user['id']
]);
