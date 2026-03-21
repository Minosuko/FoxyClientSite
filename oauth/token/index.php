<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/oauth_helper.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('method_not_allowed', 'Only POST requests are allowed.', 'METHOD_MISMATCH');
}

// Support both POST and JSON input
$input = $_POST;
if (empty($input)) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

$grant_type = $input['grant_type'] ?? '';
$client_id = $input['client_id'] ?? '';
$client_secret = $input['client_secret'] ?? '';
$code = $input['code'] ?? '';
$redirect_uri = $input['redirect_uri'] ?? '';
$verifier = $input['code_verifier'] ?? '';

// Support Basic Auth for client_id/client_secret
if (isset($_SERVER['PHP_AUTH_USER'])) {
    $client_id = $_SERVER['PHP_AUTH_USER'];
    $client_secret = $_SERVER['PHP_AUTH_PW'];
}

// 1. Validate Client
$client = validate_client($client_id);
if (!$client) {
    send_error('invalid_client', 'Client authentication failed.', 'CLIENT_NOT_FOUND');
}

// 2. Handle grant_type
if ($grant_type === 'authorization_code') {
    // Validate Code
    $stmt = $mysqli->prepare("SELECT * FROM oauth_authorization_codes WHERE authorization_code = ? AND client_id = ?");
    $stmt->bind_param("ss", $code, $client_id);
    $stmt->execute();
    $auth_code = $stmt->get_result()->fetch_assoc();

    if (!$auth_code || strtotime($auth_code['expires']) < time()) {
        send_error('invalid_grant', 'The authorization code is invalid or expired.', 'CODE_INVALID');
    }

    // Redirect URI must match if provided in authorize request
    if (!empty($auth_code['redirect_uri']) && $auth_code['redirect_uri'] !== $redirect_uri) {
        send_error('invalid_grant', 'The redirect_uri does not match.', 'REDIRECT_MISMATCH');
    }

    // PKCE Verification
    if (!empty($auth_code['code_challenge'])) {
        if (empty($verifier) || !verify_pkce($verifier, $auth_code['code_challenge'], $auth_code['code_challenge_method'])) {
            send_error('invalid_grant', 'PKCE verification failed.', 'PKCE_FAILED');
        }
    } else {
        // Confidential Client Check (if no PKCE, must have secret)
        if ($client['client_secret'] && $client['client_secret'] !== $client_secret) {
            send_error('invalid_client', 'Client secret is required and must match.', 'SECRET_MISMATCH');
        }
    }

    // 3. Issue Access Token
    $token_data = issue_access_token($client_id, $auth_code['user_id'], $auth_code['scope']);
    
    // Delete the used code
    $stmt = $mysqli->prepare("DELETE FROM oauth_authorization_codes WHERE authorization_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();

    send_json_response($token_data);
}

send_error('unsupported_grant_type', 'The requested grant type is not supported.', 'GRANT_TYPE_UNSUPPORTED');
