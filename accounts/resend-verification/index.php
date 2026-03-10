<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/mail_helper.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Find unverified user with this email
        $stmt = $mysqli->prepare("SELECT id, username, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            if ($user['is_verified'] == 1) {
                $error = "This account is already verified. You can <a href='../login/'>login</a>.";
            } else {
                // Generate new token
                $new_token = bin2hex(random_bytes(32));
                $stmt = $mysqli->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
                $stmt->bind_param("si", $new_token, $user['id']);
                
                if ($stmt->execute()) {
                    $verify_link = "http://" . $_SERVER['HTTP_HOST'] . "/accounts/verify/?token=" . $new_token;
                    
                    $email_body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #1a1a2e; color: #eee; border-radius: 12px;'>
                            <h2 style='color: #00f2ff; margin-bottom: 20px;'>Verify Your Account</h2>
                            <p>Hi <strong>" . htmlspecialchars($user['username']) . "</strong>,</p>
                            <p>Here's a new verification link for your Foxy Client account:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='" . $verify_link . "' style='display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #00f2ff, #0080ff); color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;'>Verify My Account</a>
                            </div>
                            <p style='color: #999; font-size: 0.85rem;'>If you did not request this, you can ignore this email.</p>
                        </div>";
                    
                    $mail_sent = send_mail($email, "Verify your Foxy Client account", $email_body);
                    
                    if ($mail_sent) {
                        $success = "A new verification link has been sent to your email.";
                    } else {
                        $success = "A new verification link has been generated. <br> (Development Link: <a href='$verify_link'>Verify Now</a>)";
                    }
                } else {
                    $error = "Failed to generate new token. Please try again.";
                }
            }
        } else {
            $error = "No account found with this email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification | Foxy Client</title>
    <link rel="stylesheet" href="../auth.css">
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Resend Verification</h2>
            <p>Enter your email to receive a new link</p>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-top: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-top: 20px;"><?php echo $success; ?></div>
                <div class="auth-footer" style="margin-top: 20px;">
                    Go to <a href="../login/">Login Page</a>
                </div>
            <?php else: ?>
                <form action="" method="POST" style="margin-top: 20px;">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="your@email.com">
                    </div>
                    <button type="submit" class="btn btn-primary btn-auth">RESEND LINK</button>
                </form>
                <div class="auth-footer">
                    Remembered your password? <a href="../login/">Login here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
