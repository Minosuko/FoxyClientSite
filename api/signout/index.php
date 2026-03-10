<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

$input = get_json_input();

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    send_error("ForbiddenOperationException", "Invalid credentials.");
}

$username = $input['username'];
$password = $input['password'];

$stmt = $mysqli->prepare("SELECT id, password_hash FROM users WHERE username = ? OR email = ?");
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

// Delete all tokens for this user
$stmt = $mysqli->prepare("DELETE FROM tokens WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();

http_response_code(204);
?>
