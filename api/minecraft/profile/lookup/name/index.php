<?php
require_once __DIR__ . '/../../../../../includes/db.php';
require_once __DIR__ . '/../../../../../includes/yggdrasil.php';

$path = $_SERVER['REQUEST_URI'];
$parts = explode('/', rtrim($path, '/'));
$username = end($parts);

if (!$username || $username === 'name') {
    http_response_code(404);
    exit;
}

$stmt = $mysqli->prepare("SELECT uuid, name FROM profiles WHERE name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404); // Minecraft services return 404 for not found profile
    exit;
}

$profile = $result->fetch_assoc();
$response = [
    "id" => str_replace('-', '', $profile['uuid']),
    "name" => $profile['name']
];

send_json_response($response);
?>
