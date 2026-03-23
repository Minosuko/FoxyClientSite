<?php
// OAuth2 Helper Utilities for FoxyClientSite

function validate_client($client_id, $redirect_uri = null) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM oauth_clients WHERE client_id = ?");
    $stmt->bind_param("s", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();

    if (!$client) return false;

    if ($redirect_uri) {
        // Simple string match for now, ideally support multiple/wildcard
        if ($client['redirect_uri'] !== $redirect_uri) return false;
    }

    return $client;
}

function create_authorization_code($client_id, $user_id, $redirect_uri, $scope = '', $challenge = null, $method = null) {
    global $mysqli;
    $code = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    $stmt = $mysqli->prepare("INSERT INTO oauth_authorization_codes (authorization_code, client_id, user_id, redirect_uri, expires, scope, code_challenge, code_challenge_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssss", $code, $client_id, $user_id, $redirect_uri, $expires, $scope, $challenge, $method);
    
    if ($stmt->execute()) {
        return $code;
    }
    return false;
}

function verify_pkce($verifier, $challenge, $method) {
    if ($method === 'S256') {
        $hash = hash('sha256', $verifier, true);
        $expected = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($hash));
        return hash_equals($expected, $challenge);
    }
    // 'plain' method
    return hash_equals($verifier, $challenge);
}

function issue_access_token($client_id, $user_id, $scope = '') {
    global $mysqli;
    require_once __DIR__ . '/yggdrasil.php';

    $stmtUser = $mysqli->prepare("SELECT u.username, p.uuid FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ? LIMIT 1");
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $userData = $stmtUser->get_result()->fetch_assoc();

    if (!$userData) return false;

    $uuid = $userData['uuid'] ? str_replace('-', '', $userData['uuid']) : '';
    $token_id = generate_token();
    $iat = time();
    $exp = $iat + (3600 * 24 * 30); // 30 days

    $payload = [
        "sub" => $uuid,
        "foxyclient" => true,
        "username" => $userData['username'],
        "uuid" => $uuid,
        "iat" => $iat,
        "exp" => $exp,
        "jti" => $token_id
    ];

    $token = generate_jwt($payload);
    $expires = date('Y-m-d H:i:s', $exp);

    $stmt = $mysqli->prepare("INSERT INTO oauth_access_tokens (access_token, client_id, user_id, expires, scope) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $token, $client_id, $user_id, $expires, $scope);
    
    if ($stmt->execute()) {
        $client_token = generate_uuid();
        $stmt2 = $mysqli->prepare("INSERT INTO tokens (user_id, access_token, client_token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isss", $user_id, $token, $client_token, $expires);
        $stmt2->execute();

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600 * 24 * 30,
            'scope' => $scope
        ];
    }
    return false;
}
