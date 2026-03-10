<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Get user info (including 2FA status)
$stmt = $mysqli->prepare("SELECT u.totp_enabled, u.totp_secret, p.uuid, p.name, p.skin_md5, p.cape_md5, p.is_slim, p.last_rename_at FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    die("Profile not found. Please contact support.");
}
$uuid = $profile['uuid'];
$baseUrl = get_base_url();

// Check if AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ===================== AJAX HANDLERS =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_ajax) {
    header('Content-Type: application/json');
    
    // --- Toggle Slim ---
    if (isset($_POST['toggle_slim'])) {
        // Anti-spam: 5 second cooldown
        $now = time();
        if (isset($_SESSION['last_slim_toggle']) && ($now - $_SESSION['last_slim_toggle']) < 5) {
            echo json_encode(['success' => false, 'error' => 'Please wait a few seconds before toggling again.']);
            exit;
        }
        
        $new_slim = $profile['is_slim'] ? 0 : 1;
        $stmt = $mysqli->prepare("UPDATE profiles SET is_slim = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_slim, $user_id);
        if ($stmt->execute()) {
            $_SESSION['last_slim_toggle'] = $now;
            echo json_encode(['success' => true, 'is_slim' => $new_slim, 'message' => 'Model updated to ' . ($new_slim ? 'Slim (Alex)' : 'Classic (Steve)') . '.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update model.']);
        }
        exit;
    }
    
    // --- Rename ---
    if (isset($_POST['rename'])) {
        $new_name = trim($_POST['new_name'] ?? '');
        
        if (empty($new_name)) {
            echo json_encode(['success' => false, 'error' => 'Please enter a new name.']);
            exit;
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,16}$/', $new_name)) {
            echo json_encode(['success' => false, 'error' => 'Name must be 3-16 characters (letters, numbers, underscores only).']);
            exit;
        }
        if (strtolower($new_name) === strtolower($profile['name'])) {
            echo json_encode(['success' => false, 'error' => 'New name must be different from current name.']);
            exit;
        }
        
        // Check 1-month cooldown
        if ($profile['last_rename_at']) {
            $last_rename = new DateTime($profile['last_rename_at']);
            $cooldown_end = clone $last_rename;
            $cooldown_end->modify('+1 month');
            $now_dt = new DateTime();
            if ($now_dt < $cooldown_end) {
                echo json_encode(['success' => false, 'error' => 'You can rename again after ' . $cooldown_end->format('M d, Y') . '.']);
                exit;
            }
        }
        
        // Check if name taken
        $stmt = $mysqli->prepare("SELECT id FROM profiles WHERE name = ? AND user_id != ?");
        $stmt->bind_param("si", $new_name, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'This name is already taken.']);
            exit;
        }
        
        $old_name = $profile['name'];
        
        // Update profile name and rename timestamp
        $stmt = $mysqli->prepare("UPDATE profiles SET name = ?, last_rename_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("si", $new_name, $user_id);
        if ($stmt->execute()) {
            // Insert rename history
            $stmt = $mysqli->prepare("INSERT INTO rename_history (user_id, old_name, new_name) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $old_name, $new_name);
            $stmt->execute();
            
            $_SESSION['username'] = $new_name;
            echo json_encode(['success' => true, 'message' => 'Name changed to ' . htmlspecialchars($new_name) . '!', 'new_name' => $new_name]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to rename. Please try again.']);
        }
        exit;
    }
    
    // --- 2FA Setup (Generate Secret) ---
    if (isset($_POST['setup_2fa'])) {
        require_once __DIR__ . '/../../includes/2FAGoogleAuthenticator.php';
        require_once __DIR__ . '/../../includes/QRCode.php';
        $ga = new GoogleAuthenticator();
        $secret = $ga->createSecret();
        
        // Store secret (not yet enabled)
        $stmt = $mysqli->prepare("UPDATE users SET totp_secret = ? WHERE id = ?");
        $stmt->bind_param("si", $secret, $user_id);
        $stmt->execute();
        
        // Generate QR code data URI
        $otpauth = 'otpauth://totp/FoxyClient:' . urlencode($profile['name']) . '?secret=' . $secret . '&issuer=FoxyClient';
        $qr_data = QRCode::getDataUri($otpauth, 4);
        
        echo json_encode(['success' => true, 'secret' => $secret, 'qr' => $qr_data]);
        exit;
    }
    
    // --- 2FA Verify & Enable ---
    if (isset($_POST['enable_2fa'])) {
        require_once __DIR__ . '/../../includes/2FAGoogleAuthenticator.php';
        $ga = new GoogleAuthenticator();
        $code = trim($_POST['totp_code'] ?? '');
        
        $stmt = $mysqli->prepare("SELECT totp_secret FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        if ($row && $ga->verifyCode($row['totp_secret'], $code, 2)) {
            $stmt = $mysqli->prepare("UPDATE users SET totp_enabled = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => '2FA has been enabled successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid code. Please try again.']);
        }
        exit;
    }
    
    // --- 2FA Disable ---
    if (isset($_POST['disable_2fa'])) {
        require_once __DIR__ . '/../../includes/2FAGoogleAuthenticator.php';
        $ga = new GoogleAuthenticator();
        $code = trim($_POST['totp_code'] ?? '');
        
        $stmt = $mysqli->prepare("SELECT totp_secret FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        if ($row && $ga->verifyCode($row['totp_secret'], $code, 2)) {
            $stmt = $mysqli->prepare("UPDATE users SET totp_enabled = 0, totp_secret = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => '2FA has been disabled.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid code. 2FA was not disabled.']);
        }
        exit;
    }
    
    // If no AJAX action matched, return error
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    exit;
}

// ===================== TRADITIONAL POST HANDLERS =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = __DIR__ . '/../../uploads/skins/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // --- Skin Upload ---
    if (isset($_FILES['skin']) && $_FILES['skin']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['skin']['tmp_name'];
        $file_size = $_FILES['skin']['size'];
        $file_name = $profile['uuid'] . '_skin.png';
        $file_path = $upload_dir . $file_name;
        
        if ($file_size > 20480) {
            $error = "Skin file exceeds 20KB limit.";
        } elseif (mime_content_type($file_tmp) !== 'image/png') {
            $error = "Skin must be a PNG file.";
        } else {
            // Check dimensions
            $info = getimagesize($file_tmp);
            if (!$info) {
                $error = "Invalid image file.";
            } else {
                $w = $info[0];
                $h = $info[1];
                if (!(($w === 32 && $h === 32) || ($w === 64 && $h === 64))) {
                    $error = "Skin dimensions must be 32x32 or 64x64 pixels.";
                } else {
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $skin_md5 = md5_file($file_path);
                        $is_slim = isset($_POST['is_slim']) ? 1 : 0;
                        
                        $stmt = $mysqli->prepare("UPDATE profiles SET skin_md5 = ?, is_slim = ? WHERE user_id = ?");
                        $stmt->bind_param("ssi", $skin_md5, $is_slim, $user_id);
                        $stmt->execute();
                        
                        $success = "Skin updated successfully!";
                        $profile['skin_md5'] = $skin_md5;
                        $profile['is_slim'] = $is_slim;
                    } else {
                        $error = "Failed to save skin file.";
                    }
                }
            }
        }
    }

    // --- Cape Upload ---
    if (isset($_FILES['cape']) && $_FILES['cape']['error'] === UPLOAD_ERR_OK) {
        $cape_dir = __DIR__ . '/../../uploads/capes/';
        if (!is_dir($cape_dir)) mkdir($cape_dir, 0777, true);

        $file_tmp = $_FILES['cape']['tmp_name'];
        $file_size = $_FILES['cape']['size'];
        $file_name = $profile['uuid'] . '_cape.png';
        $file_path = $cape_dir . $file_name;
        
        if ($file_size > 5120) {
            $error = "Cape file exceeds 5KB limit.";
        } elseif (mime_content_type($file_tmp) !== 'image/png') {
            $error = "Cape must be a PNG file.";
        } else {
            if (move_uploaded_file($file_tmp, $file_path)) {
                $cape_md5 = md5_file($file_path);
                
                $stmt = $mysqli->prepare("UPDATE profiles SET cape_md5 = ? WHERE user_id = ?");
                $stmt->bind_param("si", $cape_md5, $user_id);
                $stmt->execute();
                
                $success = "Cape updated successfully!";
                $profile['cape_md5'] = $cape_md5;
            } else {
                $error = "Failed to save cape file.";
            }
        }
    }

    // --- Remove Cape ---
    if (isset($_POST['remove_cape'])) {
        $stmt = $mysqli->prepare("UPDATE profiles SET cape_md5 = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success = "Cape removed successfully!";
            $profile['cape_md5'] = null;
        } else {
            $error = "Failed to remove cape.";
        }
    }
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../login/");
    exit;
}

// Get rename history
$rename_history = [];
$stmt = $mysqli->prepare("SELECT old_name, new_name, renamed_at FROM rename_history WHERE user_id = ? ORDER BY renamed_at DESC LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rh_result = $stmt->get_result();
while ($row = $rh_result->fetch_assoc()) {
    $rename_history[] = $row;
}

// Calculate rename cooldown
$can_rename = true;
$rename_available_date = null;
if ($profile['last_rename_at']) {
    $last_rename = new DateTime($profile['last_rename_at']);
    $cooldown_end = clone $last_rename;
    $cooldown_end->modify('+1 month');
    $now_dt = new DateTime();
    if ($now_dt < $cooldown_end) {
        $can_rename = false;
        $rename_available_date = $cooldown_end->format('M d, Y');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Foxy Client</title>
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/skinview3d@3.4.1/bundles/skinview3d.bundle.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 40px;
        }

        .profile-sidebar {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            height: fit-content;
            border: 1px solid var(--glass-border);
            text-align: center;
            position: sticky;
            top: 100px;
        }

        .skin-preview {
            width: 100%;
            aspect-ratio: 1/1.5;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            border: 1px solid var(--glass-border);
            box-shadow: inset 0 0 30px rgba(0, 242, 255, 0.05);
        }

        #skin_container {
            width: 100%;
            height: 100%;
            image-rendering: pixelated;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .content-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid var(--glass-border);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            padding: 25px;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.01);
        }

        .stat-card h4 {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .stat-card p {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            font-family: var(--font-heading);
        }

        .upload-section { margin-top: 40px; }
        .form-group { margin-bottom: 25px; }
        .form-group label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
        }

        input[type="file"]:hover {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.06);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 12px;
            user-select: none;
            cursor: pointer;
        }

        .checkbox-container input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        /* Slim Toggle Switch */
        .slim-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .slim-toggle-row .toggle-label {
            font-weight: 600;
            color: var(--text-main);
        }
        .slim-toggle-row .toggle-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .toggle-switch {
            position: relative;
            width: 52px;
            height: 28px;
            cursor: pointer;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 14px;
            transition: 0.3s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 22px; height: 22px;
            left: 3px; bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle-switch input:checked + .toggle-slider {
            background: var(--primary);
        }
        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(24px);
        }
        .toggle-switch.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Rename Section */
        .rename-input-row {
            display: flex;
            gap: 12px;
            align-items: stretch;
        }
        .rename-input-row input {
            flex: 1;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: var(--transition);
        }
        .rename-input-row input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 10px rgba(51, 230, 255, 0.2);
        }

        /* Rename History */
        .history-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .history-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--glass-border);
            font-size: 0.9rem;
        }
        .history-item:last-child { border-bottom: none; }
        .history-names {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .history-names .old-name {
            color: var(--text-muted);
            text-decoration: line-through;
        }
        .history-names .arrow {
            color: var(--primary);
            font-size: 0.8rem;
        }
        .history-names .new-name {
            color: var(--text-main);
            font-weight: 600;
        }
        .history-date {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* 2FA Section */
        .security-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .security-status.enabled {
            background: rgba(51, 255, 51, 0.08);
            border: 1px solid rgba(51, 255, 51, 0.2);
            color: #4dff4d;
        }
        .security-status.disabled {
            background: rgba(255, 165, 0, 0.08);
            border: 1px solid rgba(255, 165, 0, 0.2);
            color: #ffa500;
        }
        .qr-container {
            text-align: center;
            padding: 30px;
            background: rgba(255,255,255,0.02);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            margin: 20px 0;
        }
        .qr-container img {
            border-radius: 12px;
            margin-bottom: 15px;
        }
        .secret-key {
            font-family: monospace;
            letter-spacing: 2px;
            color: var(--primary);
            font-size: 1rem;
            background: rgba(0,0,0,0.3);
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-block;
            margin: 10px 0;
        }
        .totp-input {
            width: 200px;
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 8px;
            font-family: monospace;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: white;
            transition: var(--transition);
        }
        .totp-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(51, 230, 255, 0.2);
        }

        /* Alerts inline */
        .inline-alert {
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            display: none;
        }
        .inline-alert.alert-danger {
            background: rgba(255, 51, 51, 0.1);
            color: #ff4d4d;
            border: 1px solid rgba(255, 51, 51, 0.2);
        }
        .inline-alert.alert-success {
            background: rgba(51, 255, 51, 0.1);
            color: #4dff4d;
            border: 1px solid rgba(51, 255, 51, 0.2);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
        .alert-danger {
            background: rgba(255, 51, 51, 0.1);
            color: #ff4d4d;
            border: 1px solid rgba(255, 51, 51, 0.2);
        }
        .alert-success {
            background: rgba(51, 255, 51, 0.1);
            color: #4dff4d;
            border: 1px solid rgba(51, 255, 51, 0.2);
        }

        .cooldown-notice {
            color: var(--text-muted);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
            border: 1px solid var(--glass-border);
        }

        @media (max-width: 968px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            .profile-sidebar {
                position: relative;
                top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="bg-mesh"></div>
    
    <nav>
        <div class="logo-container">
            <span class="logo-text">FOXY DASHBOARD</span>
        </div>
        <ul class="nav-links">
            <li><a href="../../">Home</a></li>
            <li><a href="?logout=1" style="color: #ff4a4a; opacity: 1;">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="profile-sidebar">
            <div class="skin-preview">
                <?php if ($profile['skin_md5'] || $profile['cape_md5']): ?>
                    <canvas id="skin_container"></canvas>
                <?php else: ?>
                    <div style="text-align: center; color: var(--text-muted);">
                        <i class="fas fa-user-slash" style="font-size: 3rem; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                        <p>No Skin or Cape Active</p>
                    </div>
                <?php endif; ?>
            </div>
            <h2 id="profile-name" style="font-size: 1.5rem; margin-bottom: 8px;"><?php echo htmlspecialchars($profile['name']); ?></h2>
            <p style="color: var(--text-muted); font-size: 0.8rem; font-family: monospace; letter-spacing: 0.5px;"><?php echo $profile['uuid']; ?></p>
        </aside>

        <main class="main-content">
            <!-- Account Overview -->
            <div class="content-card">
                <h2 style="margin-bottom: 10px;">Account Overview</h2>
                <p style="color: var(--text-muted); margin-bottom: 35px;">Manage your Foxy Client character and profile settings.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="stat-grid">
                    <div class="stat-card">
                        <h4>Account Username</h4>
                        <p id="stat-username"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    <div class="stat-card">
                        <h4>Member Level</h4>
                        <p>Standard Alpha</p>
                    </div>
                    <div class="stat-card">
                        <h4>Registration Date</h4>
                        <p>March 2026</p>
                    </div>
                </div>
            </div>

            <!-- Customization -->
            <div class="content-card">
                <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-tshirt" style="color: var(--primary);"></i> Customization
                </h3>
                
                <!-- Slim Toggle (standalone, no re-upload needed) -->
                <div class="slim-toggle-row">
                    <div>
                        <div class="toggle-label">Slim Model (Alex-style)</div>
                        <div class="toggle-sub" id="slim-status">Currently: <?php echo $profile['is_slim'] ? 'Slim' : 'Classic'; ?></div>
                    </div>
                    <label class="toggle-switch" id="slim-toggle">
                        <input type="checkbox" id="slim-checkbox" <?php echo $profile['is_slim'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div id="slim-alert" class="inline-alert"></div>

                <div class="upload-section">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="file-upload-group">
                            <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-main);">Skin Texture</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="skin" id="skin-input" accept="image/png" required onchange="updateFileInfo(this, 'skin-info')">
                                <div class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span class="label-text">Click or drag skin here</span>
                                    <span class="sub-text">PNG (32x32 or 64x64), Max 20KB</span>
                                </div>
                            </div>
                            <div id="skin-info" class="file-info-display">
                                <i class="fas fa-file-image"></i>
                                <span class="file-info-text"></span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-bottom: 40px; width: 220px; justify-content: center;">
                            UPDATE SKIN
                        </button>
                    </form>

                    <div style="height: 1px; background: var(--glass-border); margin: 40px 0;"></div>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="file-upload-group">
                            <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-main);">Cape Texture</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="cape" id="cape-input" accept="image/png" required onchange="updateFileInfo(this, 'cape-info')">
                                <div class="file-input-label">
                                    <i class="fas fa-shield-alt"></i>
                                    <span class="label-text">Click or drag cape here</span>
                                    <span class="sub-text">PNG, Max 5KB</span>
                                </div>
                            </div>
                            <div id="cape-info" class="file-info-display">
                                <i class="fas fa-file-image"></i>
                                <span class="file-info-text"></span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <button type="submit" class="btn btn-secondary" style="width: 200px; justify-content: center;">
                                UPLOAD CAPE
                            </button>
                            <?php if ($profile['cape_md5']): ?>
                                <button type="submit" name="remove_cape" value="1" class="btn" style="background: rgba(255, 74, 74, 0.1); border: 1px solid rgba(255, 74, 74, 0.2); color: #f87171; width: 200px; justify-content: center;">
                                    REMOVE CAPE
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rename -->
            <div class="content-card">
                <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-pen" style="color: var(--primary);"></i> Rename
                </h3>

                <?php if ($can_rename): ?>
                    <div class="rename-input-row">
                        <input type="text" id="rename-input" placeholder="Enter new name (3-16 chars)" maxlength="16" pattern="[a-zA-Z0-9_]{3,16}">
                        <button id="rename-btn" class="btn btn-primary" style="white-space: nowrap; width: 160px; justify-content: center;">
                            RENAME
                        </button>
                    </div>
                    <div id="rename-alert" class="inline-alert"></div>
                <?php else: ?>
                    <div class="cooldown-notice">
                        <i class="fas fa-clock"></i>
                        You can rename again after <strong style="margin-left: 4px;"><?php echo $rename_available_date; ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (!empty($rename_history)): ?>
                    <div style="margin-top: 30px;">
                        <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Name History</h4>
                        <ul class="history-list" id="name-history-list">
                            <?php foreach ($rename_history as $rh): ?>
                                <li class="history-item">
                                    <div class="history-names">
                                        <span class="old-name"><?php echo htmlspecialchars($rh['old_name']); ?></span>
                                        <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                                        <span class="new-name"><?php echo htmlspecialchars($rh['new_name']); ?></span>
                                    </div>
                                    <span class="history-date"><?php echo date('M d, Y', strtotime($rh['renamed_at'])); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Security (2FA) -->
            <div class="content-card">
                <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-shield-halved" style="color: var(--primary);"></i> Security
                </h3>

                <div class="security-status <?php echo $profile['totp_enabled'] ? 'enabled' : 'disabled'; ?>" id="twofa-status-banner">
                    <i class="fas <?php echo $profile['totp_enabled'] ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <span id="twofa-status-text">
                        Two-Factor Authentication is <strong><?php echo $profile['totp_enabled'] ? 'Enabled' : 'Disabled'; ?></strong>
                    </span>
                </div>

                <div id="twofa-section">
                    <?php if ($profile['totp_enabled']): ?>
                        <!-- Disable 2FA -->
                        <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.9rem;">
                            To disable 2FA, enter your current authenticator code below.
                        </p>
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <input type="text" id="disable-2fa-code" class="totp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code">
                            <button id="disable-2fa-btn" class="btn" style="background: rgba(255, 74, 74, 0.1); border: 1px solid rgba(255, 74, 74, 0.2); color: #f87171; width: 180px; justify-content: center;">
                                DISABLE 2FA
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Enable 2FA -->
                        <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.9rem;">
                            Protect your account with an authenticator app like Google Authenticator or Authy.
                        </p>
                        <button id="setup-2fa-btn" class="btn btn-primary" style="width: 200px; justify-content: center;">
                            SETUP 2FA
                        </button>
                        <div id="twofa-setup-area" style="display: none;">
                            <div class="qr-container">
                                <p style="margin-bottom: 15px; font-weight: 600;">Scan this QR code with your authenticator app:</p>
                                <img id="twofa-qr" src="" alt="QR Code" width="200" height="200">
                                <p style="margin-top: 10px; font-size: 0.8rem; color: var(--text-muted);">Or enter this key manually:</p>
                                <div class="secret-key" id="twofa-secret"></div>
                            </div>
                            <div style="margin-top: 20px; text-align: center;">
                                <p style="margin-bottom: 12px; font-weight: 600;">Enter the 6-digit code to verify:</p>
                                <div style="display: flex; gap: 12px; align-items: center; justify-content: center; flex-wrap: wrap;">
                                    <input type="text" id="enable-2fa-code" class="totp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code">
                                    <button id="enable-2fa-btn" class="btn btn-primary" style="width: 160px; justify-content: center;">
                                        VERIFY & ENABLE
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="twofa-alert" class="inline-alert"></div>
            </div>
        </main>
    </div>

    <?php if ($profile['skin_md5'] || $profile['cape_md5']): ?>
    <script>
        var skinViewer = new skinview3d.SkinViewer({
            canvas: document.getElementById("skin_container"),
            width: 300,
            height: 450,
            alpha: true
        });

        var currentModel = "<?php echo $profile['is_slim'] ? 'slim' : 'default'; ?>";

        <?php if ($profile['skin_md5']): ?>
        skinViewer.loadSkin("<?php echo "$baseUrl/uploads/skins/{$uuid}_skin.png?md5={$profile['skin_md5']}"; ?>", {
            model: currentModel
        });
        <?php endif; ?>

        <?php if ($profile['cape_md5']): ?>
        skinViewer.loadCape("<?php echo "$baseUrl/uploads/capes/{$uuid}_cape.png?md5={$profile['cape_md5']}"; ?>");
        <?php endif; ?>

        skinViewer.animations.add(skinview3d.WalkingAnimation);
        skinViewer.animations.add(skinview3d.RotatingAnimation);
        skinViewer.controls.enableZoom = false;
        skinViewer.fov = 70;
        
        function resizeCanvas() {
            var container = document.querySelector('.skin-preview');
            skinViewer.width = container.offsetWidth;
            skinViewer.height = container.offsetHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
    </script>
    <?php endif; ?>

    <script>
    $(function() {
        function showInlineAlert(selector, msg, type) {
            $(selector).removeClass('alert-danger alert-success')
                .addClass('alert-' + type).html(msg).fadeIn(200);
            if (type === 'success') {
                setTimeout(function() { $(selector).fadeOut(300); }, 4000);
            }
        }

        // ===== Slim Toggle =====
        var slimCooldown = false;
        $('#slim-checkbox').on('change', function(e) {
            e.preventDefault();
            var $toggle = $('#slim-toggle');
            
            if (slimCooldown) {
                // Revert the checkbox
                $(this).prop('checked', !$(this).prop('checked'));
                showInlineAlert('#slim-alert', 'Please wait a few seconds before toggling again.', 'danger');
                return;
            }
            
            slimCooldown = true;
            $toggle.addClass('disabled');
            
            $.ajax({
                url: '',
                type: 'POST',
                data: { toggle_slim: 1 },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showInlineAlert('#slim-alert', res.message, 'success');
                        $('#slim-status').text('Currently: ' + (res.is_slim ? 'Slim' : 'Classic'));
                        // Reload skin viewer model if available
                        <?php if ($profile['skin_md5']): ?>
                        currentModel = res.is_slim ? 'slim' : 'default';
                        skinViewer.loadSkin("<?php echo "$baseUrl/uploads/skins/{$uuid}_skin.png?md5={$profile['skin_md5']}"; ?>", {
                            model: currentModel
                        });
                        <?php endif; ?>
                    } else {
                        showInlineAlert('#slim-alert', res.error, 'danger');
                        // Revert checkbox
                        $('#slim-checkbox').prop('checked', !$('#slim-checkbox').prop('checked'));
                    }
                },
                error: function() {
                    showInlineAlert('#slim-alert', 'Failed. Please try again.', 'danger');
                    $('#slim-checkbox').prop('checked', !$('#slim-checkbox').prop('checked'));
                },
                complete: function() {
                    setTimeout(function() {
                        slimCooldown = false;
                        $toggle.removeClass('disabled');
                    }, 5000);
                }
            });
        });

        // ===== Rename =====
        $('#rename-btn').on('click', function() {
            var newName = $.trim($('#rename-input').val());
            var $btn = $(this);
            
            if (!newName) {
                showInlineAlert('#rename-alert', 'Please enter a name.', 'danger');
                return;
            }
            if (!/^[a-zA-Z0-9_]{3,16}$/.test(newName)) {
                showInlineAlert('#rename-alert', 'Name must be 3-16 characters (letters, numbers, underscores).', 'danger');
                return;
            }
            
            $btn.prop('disabled', true);
            $('#rename-alert').fadeOut(100);
            
            $.ajax({
                url: '',
                type: 'POST',
                data: { rename: 1, new_name: newName },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showInlineAlert('#rename-alert', res.message, 'success');
                        $('#profile-name').text(res.new_name);
                        $('#stat-username').text(res.new_name);
                        $('#rename-input').val('');
                        // Add to history list
                        var currentName = $('#profile-name').text();
                        // Reload page after short delay to refresh cooldown
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showInlineAlert('#rename-alert', res.error, 'danger');
                    }
                    $btn.prop('disabled', false);
                },
                error: function() {
                    showInlineAlert('#rename-alert', 'Failed. Please try again.', 'danger');
                    $btn.prop('disabled', false);
                }
            });
        });

        // Enter key for rename
        $('#rename-input').on('keypress', function(e) {
            if (e.which === 13) { e.preventDefault(); $('#rename-btn').click(); }
        });

        // ===== 2FA Setup =====
        $('#setup-2fa-btn').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Setting up...');
            
            $.ajax({
                url: '',
                type: 'POST',
                data: { setup_2fa: 1 },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#twofa-qr').attr('src', res.qr);
                        $('#twofa-secret').text(res.secret);
                        $btn.fadeOut(200, function() {
                            $('#twofa-setup-area').fadeIn(300);
                            $('#enable-2fa-code').focus();
                        });
                    } else {
                        showInlineAlert('#twofa-alert', 'Failed to setup 2FA.', 'danger');
                        $btn.prop('disabled', false).text('SETUP 2FA');
                    }
                },
                error: function() {
                    showInlineAlert('#twofa-alert', 'Failed. Please try again.', 'danger');
                    $btn.prop('disabled', false).text('SETUP 2FA');
                }
            });
        });

        // ===== Enable 2FA =====
        $('#enable-2fa-btn').on('click', function() {
            var code = $.trim($('#enable-2fa-code').val());
            if (!code || code.length !== 6) {
                showInlineAlert('#twofa-alert', 'Please enter a valid 6-digit code.', 'danger');
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: '',
                type: 'POST',
                data: { enable_2fa: 1, totp_code: code },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showInlineAlert('#twofa-alert', res.message, 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showInlineAlert('#twofa-alert', res.error, 'danger');
                        $btn.prop('disabled', false);
                        $('#enable-2fa-code').val('').focus();
                    }
                },
                error: function() {
                    showInlineAlert('#twofa-alert', 'Verification failed.', 'danger');
                    $btn.prop('disabled', false);
                }
            });
        });

        // ===== Disable 2FA =====
        $('#disable-2fa-btn').on('click', function() {
            var code = $.trim($('#disable-2fa-code').val());
            if (!code || code.length !== 6) {
                showInlineAlert('#twofa-alert', 'Please enter your current 6-digit code.', 'danger');
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: '',
                type: 'POST',
                data: { disable_2fa: 1, totp_code: code },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showInlineAlert('#twofa-alert', res.message, 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showInlineAlert('#twofa-alert', res.error, 'danger');
                        $btn.prop('disabled', false);
                        $('#disable-2fa-code').val('').focus();
                    }
                },
                error: function() {
                    showInlineAlert('#twofa-alert', 'Failed. Please try again.', 'danger');
                    $btn.prop('disabled', false);
                }
            });
        });
    });
    function updateFileInfo(input, infoId) {
        const display = document.getElementById(infoId);
        const text = display.querySelector('.file-info-text');
        if (input.files && input.files[0]) {
            const file = input.files[0];
            text.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
            display.style.display = 'flex';
            
            // Dim the label if file selected
            const wrapper = input.parentElement;
            const label = wrapper.querySelector('.file-input-label');
            label.style.opacity = '0.5';
            label.querySelector('.label-text').textContent = "File Selected";
        }
    }
    </script>
</body>
</html>
