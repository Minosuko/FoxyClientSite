<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/oauth_helper.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

// 1. Validate Client
$client_id = $_GET['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? '';
$response_type = $_GET['response_type'] ?? '';
$state = $_GET['state'] ?? '';
$scope = $_GET['scope'] ?? '';
$challenge = $_GET['code_challenge'] ?? null;
$method = $_GET['code_challenge_method'] ?? 'plain';

$client = validate_client($client_id, $redirect_uri);
if (!$client) {
    http_response_code(400);
    die("Invalid client_id or redirect_uri.");
}

if ($response_type !== 'code') {
    http_response_code(400);
    die("Unsupported response_type. Only 'code' is supported.");
}

// 2. Check Session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    $current_url = $_SERVER['REQUEST_URI'];
    header("Location: ../../accounts/login/?return=" . urlencode($current_url));
    exit;
}

// 3. User is logged in - Require manual approval
if (isset($_POST['approve'])) {
    $code = create_authorization_code($client_id, $_SESSION['user_id'], $redirect_uri, $scope, $challenge, $method);
    if ($code) {
        $query = http_build_query([
            'code' => $code,
            'state' => $state
        ]);
        header("Location: " . $redirect_uri . (strpos($redirect_uri, '?') === false ? '?' : '&') . $query);
        exit;
    }
    http_response_code(500);
    die("Internal Server Error: Failed to create authorization code.");
}

// 4. Show Approval Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorize Application | Foxy Client</title>
    <link rel="stylesheet" href="../../accounts/auth.css">
    <style>
        .auth-card { text-align: center; }
        .client-name { color: var(--primary); font-weight: bold; font-size: 1.2rem; }
        .scope-list { text-align: left; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid var(--glass-border); }
        .scope-item { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 0.9rem; }
        .scope-item i { color: var(--primary); }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="bg-mesh"></div>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Authorize Application</h2>
            <p>An application is requesting access to your Foxy Client account.</p>
            
            <div class="client-name"><?php echo htmlspecialchars($client['client_id']); ?></div>
            
            <div class="scope-list">
                <div class="scope-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Read your profile information (Username, UUID)</span>
                </div>
                <div class="scope-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Access your skin and cape data</span>
                </div>
            </div>

            <form method="POST">
                <button type="submit" name="approve" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                    AUTHORIZE
                </button>
                <a href="../../accounts/dashboard/" class="btn btn-secondary" style="width: 100%; display: block; text-decoration: none; padding: 12px 0;">
                    CANCEL
                </a>
            </form>
        </div>
    </div>
</body>
</html>
