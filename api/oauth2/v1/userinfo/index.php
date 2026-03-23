<?php
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/yggdrasil.php';

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

// 2. Validate JWT Token
$payload = verify_jwt($token);
if (!$payload) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'The access token is invalid (bad signature).']);
    exit;
}

// Check expiry
if (isset($payload['exp']) && $payload['exp'] < time()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'The access token has expired.']);
    exit;
}

// 3. Check revocation (token must still exist in DB)
$stmt = $mysqli->prepare("SELECT 1 FROM oauth_access_tokens WHERE access_token = ? AND expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'The access token has been revoked or expired.']);
    exit;
}

// 4. Get user_id from JWT payload
$user_id = $payload['sub'] ?? null;
if (!$user_id) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'Token missing subject claim.']);
    exit;
}

// 5. Fetch User Info
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

// 6. Return Response
header('Content-Type: application/json');
echo json_encode([
    'username' => $user['username'],
    'email' => $user['email'],
    'uuid' => $user['uuid'] ?? "",
    'profile_name' => $user['profile_name'] ?? $user['username'],
    'id' => $user['id']
]);
