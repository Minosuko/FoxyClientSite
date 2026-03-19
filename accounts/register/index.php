<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/yggdrasil.php';
require_once __DIR__ . '/../../includes/mail_helper.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,16}$/', $username)) {
        $error = "Username must be 3-16 characters (letters, numbers, underscores only).";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username or email exists
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $verification_token = bin2hex(random_bytes(32));
            
            $stmt = $mysqli->prepare("INSERT INTO users (username, email, password_hash, verification_token) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password_hash, $verification_token);
            
            if ($stmt->execute()) {
                $user_id = $mysqli->insert_id;
                
                // Create profile automatically
                $uuid = generate_uuid();
                $stmt = $mysqli->prepare("INSERT INTO profiles (user_id, uuid, name) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $uuid, $username);
                $stmt->execute();
                
                // Send verification email via PHPMailer
                $verify_link = "http://" . $_SERVER['HTTP_HOST'] . "/accounts/verify/?token=" . $verification_token;
                $email_body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #1a1a2e; color: #eee; border-radius: 12px;'>
                        <h2 style='color: #00f2ff; margin-bottom: 20px;'>Welcome to Foxy Client!</h2>
                        <p>Hi <strong>" . htmlspecialchars($username) . "</strong>,</p>
                        <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='" . $verify_link . "' style='display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #00f2ff, #0080ff); color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;'>Verify My Account</a>
                        </div>
                        <p style='color: #999; font-size: 0.85rem;'>If you did not register for this account, you can ignore this email.</p>
                    </div>";
                
                $mail_sent = send_mail($email, "Verify your Foxy Client account", $email_body);
                
                if ($mail_sent) {
                    $success = "Registration successful! Please check your email to verify your account.";
                } else {
                    $success = "Registration successful! Please check your email to verify your account. <br> (Development Link: <a href='$verify_link'>Verify Now</a>)";
                }
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        if ($error) {
            echo json_encode(['success' => false, 'error' => $error]);
        } else {
            echo json_encode(['success' => true, 'message' => $success]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Foxy Client</title>
    <link rel="stylesheet" href="../auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
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
            <h2>Create Account</h2>
            <p>Join the Foxy Client community</p>

            <div id="register-alert" style="display:none;" class="alert"></div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form id="register-form" action="" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Pick a username (3-16 chars)">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm your password">
                </div>
                <button type="submit" id="register-btn" class="btn btn-primary btn-auth">
                    REGISTER <span class="spinner" id="register-spinner"></span>
                </button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="../login/">Login here</a>
            </div>
        </div>
    </div>

    <script>
    $(function() {
        function showAlert(msg, type) {
            $('#register-alert').removeClass('alert-danger alert-success')
                .addClass('alert-' + type).html(msg).fadeIn(200);
        }

        $('#register-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $('#register-btn');
            $btn.prop('disabled', true);
            $('#register-spinner').addClass('active');
            $('#register-alert').fadeOut(100);

            $.ajax({
                url: '',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showAlert(res.message, 'success');
                        $('#register-form')[0].reset();
                    } else {
                        showAlert(res.error, 'danger');
                    }
                    $btn.prop('disabled', false);
                    $('#register-spinner').removeClass('active');
                },
                error: function() {
                    showAlert('Something went wrong. Please try again.', 'danger');
                    $btn.prop('disabled', false);
                    $('#register-spinner').removeClass('active');
                }
            });
        });
    });
    </script>
</body>
</html>
