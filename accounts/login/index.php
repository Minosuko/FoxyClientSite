<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle 2FA verification
    if (isset($_POST['verify_2fa'])) {
        $code = trim($_POST['totp_code'] ?? '');
        
        if (!isset($_SESSION['pending_2fa_user_id'])) {
            $resp = ['success' => false, 'error' => 'No pending 2FA session.'];
        } elseif (empty($code)) {
            $resp = ['success' => false, 'error' => 'Please enter your 2FA code.'];
        } else {
            require_once __DIR__ . '/../../includes/2FAGoogleAuthenticator.php';
            $ga = new GoogleAuthenticator();
            
            $uid = $_SESSION['pending_2fa_user_id'];
            $stmt = $mysqli->prepare("SELECT totp_secret FROM users WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            
            if ($row && $ga->verifyCode($row['totp_secret'], $code, 2)) {
                // 2FA passed — set session
                $stmt2 = $mysqli->prepare("SELECT username FROM users WHERE id = ?");
                $stmt2->bind_param("i", $uid);
                $stmt2->execute();
                $u = $stmt2->get_result()->fetch_assoc();
                
                $_SESSION['user_id'] = $uid;
                $_SESSION['username'] = $u['username'];
                unset($_SESSION['pending_2fa_user_id']);
                $resp = ['success' => true, 'redirect' => '../dashboard/'];
            } else {
                $resp = ['success' => false, 'error' => 'Invalid 2FA code.'];
            }
        }
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode($resp);
            exit;
        }
        // Fallback for non-ajax handled below
    }
    
    // Handle normal login
    if (!isset($_POST['verify_2fa'])) {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $error = '';
        
        if (empty($login) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            $stmt = $mysqli->prepare("SELECT id, username, password_hash, is_verified, totp_enabled FROM users WHERE username = ? OR email = ?");
            if (!$stmt) die("Prepare failed: " . $mysqli->error);
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password_hash'])) {
                    if ($user['is_verified'] == 0) {
                        $error = "Your account is not verified. Please check your email.";
                    } elseif ($user['totp_enabled'] == 1) {
                        // 2FA required — don't set session yet
                        $_SESSION['pending_2fa_user_id'] = $user['id'];
                        if ($is_ajax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'needs_2fa' => true]);
                            exit;
                        }
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        if ($is_ajax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'redirect' => '../dashboard/']);
                            exit;
                        }
                        header("Location: ../dashboard/");
                        exit;
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        }
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}

$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Foxy Client</title>
    <link rel="stylesheet" href="../auth.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .two-fa-section { display: none; margin-top: 20px; }
        .two-fa-section.active { display: block; }
        .btn-auth:disabled { opacity: 0.6; cursor: not-allowed; }
        .spinner { display: none; margin-left: 8px; }
        .spinner.active { display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner::after {
            content: ''; display: inline-block; width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff;
            border-radius: 50%; animation: spin 0.6s linear infinite; vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="bg-mesh"></div>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Welcome Back</h2>
            <p>Login to manage your profile</p>

            <div id="login-alert" style="display:none;" class="alert"></div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form id="login-form" action="" method="POST">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="login" id="login-input" required placeholder="Enter username or email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password-input" required placeholder="Enter your password">
                </div>
                <button type="submit" id="login-btn" class="btn btn-primary btn-auth">
                    LOGIN <span class="spinner" id="login-spinner"></span>
                </button>
            </form>

            <div id="two-fa-section" class="two-fa-section">
                <div style="height: 1px; background: var(--glass-border); margin: 20px 0;"></div>
                <h3 style="text-align:center; margin-bottom: 10px; font-size: 1.1rem;">Two-Factor Authentication</h3>
                <p style="text-align:center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Enter the 6-digit code from your authenticator app</p>
                <form id="twofa-form">
                    <div class="form-group">
                        <label>2FA Code</label>
                        <input type="text" name="totp_code" id="totp-input" required placeholder="000000" 
                               maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code"
                               style="text-align: center; font-size: 1.5rem; letter-spacing: 8px; font-family: monospace;">
                    </div>
                    <button type="submit" id="twofa-btn" class="btn btn-primary btn-auth">
                        VERIFY <span class="spinner" id="twofa-spinner"></span>
                    </button>
                </form>
            </div>

            <div class="auth-footer">
                Don't have an account? <a href="../register/">Register here</a><br>
                <a href="../forgot-password/" style="font-size: 0.8rem; margin-top: 10px; display: inline-block;">Forgot Password?</a>
            </div>
        </div>
    </div>

    <script>
    $(function() {
        function showAlert(msg, type) {
            $('#login-alert').removeClass('alert-danger alert-success')
                .addClass('alert-' + type).html(msg).fadeIn(200);
        }

        // Login form
        $('#login-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $('#login-btn');
            $btn.prop('disabled', true);
            $('#login-spinner').addClass('active');
            $('#login-alert').fadeOut(100);

            $.ajax({
                url: '',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showAlert('Login successful! Redirecting...', 'success');
                        setTimeout(function() { window.location.href = res.redirect; }, 500);
                    } else if (res.needs_2fa) {
                        $('#login-form').fadeOut(200, function() {
                            $('#two-fa-section').addClass('active').hide().fadeIn(300);
                            $('#totp-input').focus();
                        });
                        $btn.prop('disabled', false);
                        $('#login-spinner').removeClass('active');
                    } else {
                        showAlert(res.error, 'danger');
                        $btn.prop('disabled', false);
                        $('#login-spinner').removeClass('active');
                    }
                },
                error: function() {
                    showAlert('Something went wrong. Please try again.', 'danger');
                    $btn.prop('disabled', false);
                    $('#login-spinner').removeClass('active');
                }
            });
        });

        // 2FA form
        $('#twofa-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $('#twofa-btn');
            $btn.prop('disabled', true);
            $('#twofa-spinner').addClass('active');
            $('#login-alert').fadeOut(100);

            $.ajax({
                url: '',
                type: 'POST',
                data: { verify_2fa: 1, totp_code: $('#totp-input').val() },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showAlert('Verified! Redirecting...', 'success');
                        setTimeout(function() { window.location.href = res.redirect; }, 500);
                    } else {
                        showAlert(res.error, 'danger');
                        $btn.prop('disabled', false);
                        $('#twofa-spinner').removeClass('active');
                        $('#totp-input').val('').focus();
                    }
                },
                error: function() {
                    showAlert('Verification failed. Please try again.', 'danger');
                    $btn.prop('disabled', false);
                    $('#twofa-spinner').removeClass('active');
                }
            });
        });
    });
    </script>
</body>
</html>
