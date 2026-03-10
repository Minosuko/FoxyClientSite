<?php
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/yggdrasil.php';

// Check if we have a username in the path or as a param
// For /api/profiles/minecraft/byname/{username}
$path = $_SERVER['REQUEST_URI'];
$parts = explode('/', rtrim($path, '/'));
$username = end($parts);

if (!$username || $username === 'byname') {
    http_response_code(404);
    exit;
}

$stmt = $mysqli->prepare("SELECT uuid, name FROM profiles WHERE name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(204);
    exit;
}

$profile = $result->fetch_assoc();
$response = [
    "id" => str_replace('-', '', $profile['uuid']),
    "name" => $profile['name']
];

send_json_response($response);
?>
