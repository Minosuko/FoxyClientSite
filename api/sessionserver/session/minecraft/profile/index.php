<?php
require_once __DIR__ . '/../../../../../includes/db.php';
require_once __DIR__ . '/../../../../../includes/yggdrasil.php';

$uuid = isset($_GET['uuid']) ? $_GET['uuid'] : null;

if (!$uuid) {
    http_response_code(400);
    exit;
}

// Support both dashed and undashed UUIDs
if (strlen($uuid) === 32) {
    $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
}

// Get full profile with textures
$stmt = $mysqli->prepare("SELECT uuid, name, skin_md5, cape_md5, is_slim FROM profiles WHERE uuid = ?");
$stmt->bind_param("s", $uuid);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    http_response_code(204); // No Content
    exit;
}

$cleanUuid = str_replace('-', '', $profile['uuid']);

$textures = [];
$baseUrl = get_base_url();
if ($profile['skin_md5']) {
    $md5 = $profile['skin_md5'];
    $url = "$baseUrl/uploads/skins/{$uuid}_skin.png?md5=$md5";
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
if ($profile['cape_md5']) {
    $md5 = $profile['cape_md5'];
    $url = "$baseUrl/uploads/capes/{$uuid}_cape.png?md5=$md5";
    if (strpos($url, '//') === 0) {
        $url = (strpos($baseUrl, 'https') === 0 ? 'https:' : 'http:') . $url;
    }
    $textures['CAPE'] = [
        "url" => $url
    ];
}

$value = [
    "timestamp" => time() * 1000,
    "profileId" => $cleanUuid,
    "profileName" => $profile['name'],
    "textures" => $textures
];

$encodedValue = base64_encode(json_encode($value));

$response = [
    "id" => $cleanUuid,
    "name" => $profile['name'],
    "properties" => [
        [
            "name" => "textures",
            "value" => $encodedValue,
            "signature" => sign_profile($encodedValue)
        ]
    ]
];

send_json_response($response);
?>
