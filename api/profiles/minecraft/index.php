<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

// Handle /api/profiles/minecraft/{uuid}
$path = $_SERVER['REQUEST_URI'];
$parts = explode('/', rtrim($path, '/'));
$uuid = end($parts);

// Check if UUID is valid (32 chars hex or 36 chars with dashes)
if (strlen($uuid) !== 32 && strlen($uuid) !== 36) {
     http_response_code(404);
     exit;
}

// Convert to dashed format if needed for DB query (assuming DB stores with dashes)
if (strlen($uuid) === 32) {
    $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
}

$stmt = $mysqli->prepare("SELECT uuid, name, skin_url, cape_url, is_slim FROM profiles WHERE uuid = ?");
$stmt->bind_param("s", $uuid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(204);
    exit;
}

$profile = $result->fetch_assoc();
$uuidHex = str_replace('-', '', $profile['uuid']);

$textures = [];
$baseUrl = get_base_url();
if ($profile['skin_url']) {
    $url = $profile['skin_url'];
    if (strpos($url, '//') === 0) {
        $url = (strpos($baseUrl, 'https') === 0 ? 'https:' : 'http:') . $url;
    }
    $textures['SKIN'] = [
        "url" => $url
    ];
    if ($profile['is_slim']) {
        $textures['SKIN']['metadata'] = ["model" => "slim"];
    }
}
if ($profile['cape_url']) {
    $url = $profile['cape_url'];
    if (strpos($url, '//') === 0) {
        $url = (strpos($baseUrl, 'https') === 0 ? 'https:' : 'http:') . $url;
    }
    $textures['CAPE'] = [
        "url" => $url
    ];
}

$value = [
    "timestamp" => time() * 1000,
    "profileId" => $uuidHex,
    "profileName" => $profile['name'],
    "textures" => $textures
];

$response = [
    "id" => $uuidHex,
    "name" => $profile['name'],
    "properties" => [
        [
            "name" => "textures",
            "value" => base64_encode(json_encode($value)),
            "signature" => sign_profile(base64_encode(json_encode($value)))
        ]
    ]
];

send_json_response($response);
?>
