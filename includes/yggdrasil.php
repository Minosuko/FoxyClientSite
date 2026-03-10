<?php
// Yggdrasil Common Utilities

function get_json_input() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

function send_json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function send_error($error, $errorMessage, $cause = null) {
    $response = array(
        "error" => $error,
        "errorMessage" => $errorMessage
    );
    if ($cause) {
        $response["cause"] = $cause;
    }
    send_json_response($response, 400);
}

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function generate_token() {
    return bin2hex(random_bytes(16));
}

// Function to sign profile response (simplified for now, usually requires full rsa signature)
// authlib-injector expects a signature if public keys are provided
function sign_profile($data, $private_key_path = null) {
    if (!$private_key_path) {
        $keyConfig = require __DIR__ . '/../config/yggdrasil.php';
        $priv_key_content = $keyConfig['private_key'];
    } else {
        if (!file_exists($private_key_path)) return null;
        $priv_key_content = file_get_contents($private_key_path);
    }
    
    $priv_key = openssl_pkey_get_private($priv_key_content);
    if (!$priv_key) return null;
    
    $signature = "";
    if (openssl_sign($data, $signature, $priv_key, OPENSSL_ALGO_SHA1)) {
        return base64_encode($signature);
    }
    return null;
}
function base64url_encode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

function base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
}

function generate_jwt($payload) {
    $secConfig = require __DIR__ . '/../config/security.php';
    $secret = $secConfig['jwt_secret'];

    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    
    $base64UrlHeader = base64url_encode($header);
    $base64UrlPayload = base64url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = base64url_encode($signature);

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function verify_jwt($jwt) {
    if (!$jwt) return null;
    $secConfig = require __DIR__ . '/../config/security.php';
    $secret = $secConfig['jwt_secret'];

    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;

    list($header, $payload, $signature) = $parts;

    $decodedSignature = base64url_decode($signature);
    $data = $header . "." . $payload;

    $expectedSignature = hash_hmac('sha256', $data, $secret, true);

    if (hash_equals($expectedSignature, $decodedSignature)) {
        return json_decode(base64url_decode($payload), true);
    }
    return null;
}

function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    // Check for proxy-forwarded protocol
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }
    return $protocol . "://" . $_SERVER['HTTP_HOST'];
}

/*
 * TOTP Verification
 */
function verify_totp($secret, $code) {
    require_once __DIR__ . '/2FAGoogleAuthenticator.php';
    $ga = new GoogleAuthenticator();
    // 2 * 30s = 1 minute clock tolerance
    return $ga->verifyCode($secret, $code, 2);
}
