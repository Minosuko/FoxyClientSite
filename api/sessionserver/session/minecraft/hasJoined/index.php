<?php
require_once __DIR__ . '/../../../../../includes/db.php';
require_once __DIR__ . '/../../../../../includes/yggdrasil.php';

if (!isset($_GET['username']) || !isset($_GET['serverId'])) {
    http_response_code(400);
    exit;
}

$username = $_GET['username'];
$serverId = $_GET['serverId'];
$ip = isset($_GET['ip']) ? $_GET['ip'] : null;

// Find session
$sql = "SELECT s.uuid, p.name FROM sessions s JOIN profiles p ON s.uuid = p.uuid WHERE p.name = ? AND s.server_id = ?";
if ($ip) {
    $sql .= " AND s.ip = ?";
}
$sql .= " ORDER BY s.created_at DESC LIMIT 1";

$stmt = $mysqli->prepare($sql);
if ($ip) {
    $stmt->bind_param("sss", $username, $serverId, $ip);
} else {
    $stmt->bind_param("ss", $username, $serverId);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(204); // No Content - verify failed
    exit;
}

$session = $result->fetch_assoc();
$uuid = str_replace('-', '', $session['uuid']);
if (strlen($uuid) === 32) {
    $fuuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
}
// Get full profile with textures
$stmt = $mysqli->prepare("SELECT uuid, name, skin_md5, cape_md5, is_slim FROM profiles WHERE uuid = ?");
$stmt->bind_param("s", $session['uuid']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

$textures = [];
$baseUrl = get_base_url();
if ($profile['skin_md5']) {
    $md5 = $profile['skin_md5'];
    $url = "$baseUrl/uploads/skins/{$fuuid}_skin.png?md5=$md5";
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
    $url = "$baseUrl/uploads/capes/{$fuuid}_cape.png?md5=$md5";
    if (strpos($url, '//') === 0) {
        $url = (strpos($baseUrl, 'https') === 0 ? 'https:' : 'http:') . $url;
    }
    $textures['CAPE'] = [
        "url" => $url
    ];
}

$value = [
    "timestamp" => time() * 1000,
    "profileId" => $uuid,
    "profileName" => $profile['name'],
    "textures" => $textures
];

$response = [
    "id" => $uuid,
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
