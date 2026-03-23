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

    $expires_in = 3600 * 24 * 30; // 30 days
    $expires = date('Y-m-d H:i:s', time() + $expires_in);

    // Fetch user profile for JWT claims
    $stmt = $mysqli->prepare("SELECT u.username, p.uuid, p.name as profile_name FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    $profile_uuid = $user && $user['uuid'] ? str_replace('-', '', $user['uuid']) : '';

    // Generate JWT token
    $payload = [
        'sub' => (string)$user_id,
        'client_id' => $client_id,
        'scope' => $scope,
        'foxyclient' => true,
        'username' => $user ? $user['username'] : '',
        'uuid' => $profile_uuid,
        'iat' => time(),
        'exp' => time() + $expires_in,
        'jti' => bin2hex(random_bytes(16))
    ];
    $token = generate_jwt($payload);

    // Store in DB for revocation support
    $stmt = $mysqli->prepare("INSERT INTO oauth_access_tokens (access_token, client_id, user_id, expires, scope) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $token, $client_id, $user_id, $expires, $scope);
    
    if ($stmt->execute()) {
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expires_in,
            'scope' => $scope
        ];
    }
    return false;
}
