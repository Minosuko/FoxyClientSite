<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $uuid = $_GET['UUID'] ?? '';
    if (empty($uuid)) {
        send_error("IllegalArgumentException", "UUID is required.");
    }

    // Convert to dashed format if needed
    if (strlen($uuid) === 32) {
        $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
    }

    $stmt = $mysqli->prepare("SELECT uuid, name, skin_md5, cape_md5, is_slim FROM profiles WHERE uuid = ?");
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(204);
        exit;
    }

    $profile = $result->fetch_assoc();
    $baseUrl = get_base_url();
    
    $textures = [];
    if ($profile['skin_md5']) {
        $textures['SKIN'] = [
            "url" => "$baseUrl/uploads/skins/{$uuid}_skin.png?md5=" . $profile['skin_md5'],
            "metadata" => $profile['is_slim'] ? ["model" => "slim"] : ["model" => "default"]
        ];
    }
    if ($profile['cape_md5']) {
        $textures['CAPE'] = [
            "url" => "$baseUrl/uploads/capes/{$uuid}_" . $profile['cape_md5'] . "_cape.png"
        ];
    }

    send_json_response([
        "id" => str_replace('-', '', $profile['uuid']),
        "name" => $profile['name'],
        "textures" => $textures
    ]);
} 

elseif ($method === 'POST') {
    $token = $_POST['accesstoken'] ?? '';
    if (empty($token)) {
        send_error("ForbiddenOperationException", "Access token is required.");
    }

    $payload = verify_jwt($token);
    if (!$payload) {
        send_error("ForbiddenOperationException", "Invalid or expired access token.");
    }

    $uuid = $payload['uuid']; // Assuming JWT payload contains the dashed UUID
    if (strlen($uuid) === 32) {
        $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
    }
    $updated = false;

    // Handle Skin
    if (isset($_FILES['skin'])) {
        $file = $_FILES['skin'];
        if ($file['size'] > 20480) {
            send_error("IllegalArgumentException", "Skin file exceeds 20KB limit.");
        }
        
        $info = getimagesize($file['tmp_name']);
        if (!$info || $info[2] !== IMAGETYPE_PNG) {
            send_error("IllegalArgumentException", "Skin must be a PNG image.");
        }

        $w = $info[0]; $h = $info[1];
        if (!(($w === 32 && $h === 32) || ($w === 64 && $h === 64))) {
            send_error("IllegalArgumentException", "Skin dimensions must be 32x32 or 64x64.");
        }

        $dest = __DIR__ . "/../../uploads/skins/{$uuid}_skin.png";
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $md5 = md5_file($dest);
            $variant = ($_POST['variant'] ?? '') === 'slim' ? 1 : 0;
            $stmt = $mysqli->prepare("UPDATE profiles SET skin_md5 = ?, is_slim = ? WHERE uuid = ?");
            $stmt->bind_param("sis", $md5, $variant, $uuid);
            $stmt->execute();
            $updated = true;
        } else {
            send_error("InternalServerError", "Failed to save skin.");
        }
    }

    // Handle Cape
    if (isset($_FILES['cape'])) {
        $file = $_FILES['cape'];
        if ($file['size'] > 5120) {
            send_error("IllegalArgumentException", "Cape file exceeds 5KB limit.");
        }

        $info = getimagesize($file['tmp_name']);
        if (!$info || $info[2] !== IMAGETYPE_PNG) {
            send_error("IllegalArgumentException", "Cape must be a PNG image.");
        }

        $md5 = md5_file($file['tmp_name']);
        $dest = __DIR__ . "/../../uploads/capes/{$uuid}_{$md5}_cape.png";

        // Cleanup old cape
        $stmt = $mysqli->prepare("SELECT cape_md5 FROM profiles WHERE uuid = ?");
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        if ($old && $old['cape_md5']) {
            $old_path = __DIR__ . "/../../uploads/capes/{$uuid}_" . $old['cape_md5'] . "_cape.png";
            if (file_exists($old_path)) unlink($old_path);
        }

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt = $mysqli->prepare("UPDATE profiles SET cape_md5 = ? WHERE uuid = ?");
            $stmt->bind_param("ss", $md5, $uuid);
            $stmt->execute();
            $updated = true;
        } else {
            send_error("InternalServerError", "Failed to save cape.");
        }
    }

    if ($updated) {
        http_response_code(204);
        exit;
    } else {
        send_error("IllegalArgumentException", "No files provided for update.");
    }
}

else {
    http_response_code(405);
    exit;
}
