<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

/**
 * FoxyClient Textures API
 * Returns unencoded SKIN and CAPE textures for a given UUID.
 */

// 1. Get identifier (UUID or Username)
$uuid = $_GET['uuid'] ?? null;
$username = $_GET['username'] ?? null;

if (!$uuid && !$username) {
    http_response_code(400);
    send_json_response(['error' => 'Missing uuid or username parameter']);
}

// 2. Fetch Profile
if ($uuid) {
    // Support both dashed and undashed UUIDs
    if (strlen($uuid) === 32) {
        $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
    }
    $stmt = $mysqli->prepare("SELECT uuid, name, skin_md5, cape_md5, is_slim FROM profiles WHERE uuid = ?");
    $stmt->bind_param("s", $uuid);
} else { // Must be username
    $stmt = $mysqli->prepare("SELECT uuid, name, skin_md5, cape_md5, is_slim FROM profiles WHERE name = ?");
    $stmt->bind_param("s", $username);
}

$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    http_response_code(404);
    send_json_response(['error' => 'Profile not found']);
}

// 3. Build Textures Object
$textures = [];
$baseUrl = get_base_url();
$uuid = $uuid ?? $profile['uuid'];

if ($profile['skin_md5']) {
    $md5 = $profile['skin_md5'];
    $url = "$baseUrl/uploads/skins/{$uuid}_skin.png?md5=$md5";
    // Ensure absolute URL
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
    $url = "$baseUrl/uploads/capes/{$uuid}_{$md5}_cape.png";
    if (strpos($url, '//') === 0) {
        $url = (strpos($baseUrl, 'https') === 0 ? 'https:' : 'http:') . $url;
    }
    
    $textures['CAPE'] = [
        "url" => $url
    ];
}

// 4. Return same format as inner sessionserver textures
send_json_response($textures);
