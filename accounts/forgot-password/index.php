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
        // Find user by email
        $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Generate reset token and expiration (1 hour)
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
            
            $stmt = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expires, $user['id']);
            
            if ($stmt->execute()) {
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/accounts/reset-password/?token=" . $token;
                
                $email_body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #1a1a2e; color: #eee; border-radius: 12px;'>
                        <h2 style='color: #00f2ff; margin-bottom: 20px;'>Password Reset Request</h2>
                        <p>Hi <strong>" . htmlspecialchars($user['username']) . "</strong>,</p>
                        <p>We received a request to reset your password. Click the button below to set a new password:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='" . $reset_link . "' style='display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #00f2ff, #0080ff); color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;'>Reset My Password</a>
                        </div>
                        <p style='color: #999; font-size: 0.85rem;'>This link expires in 1 hour. If you did not request this, you can ignore this email.</p>
                    </div>";
                
                $mail_sent = send_mail($email, "Reset your Foxy Client password", $email_body);
                
                if ($mail_sent) {
                    $success = "A password reset link has been sent to your email.";
                } else {
                    $success = "We've generated a password reset link for you. <br> (Development Link: <a href='$reset_link'>Reset Password</a>)";
                }
            } else {
                $error = "Failed to process request. Please try again later.";
            }
        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Foxy Client</title>
    <link rel="stylesheet" href="../auth.css">
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Forgot Password</h2>
            <p>Enter your email to reset your memory</p>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-top: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-top: 20px;"><?php echo $success; ?></div>
                <div class="auth-footer" style="margin-top: 20px;">
                    <a href="../login/">Return to Login</a>
                </div>
            <?php else: ?>
                <form action="" method="POST" style="margin-top: 20px;">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="your@email.com">
                    </div>
                    <button type="submit" class="btn btn-primary btn-auth">SEND RESET LINK</button>
                </form>
                <div class="auth-footer">
                    Wait, I remember now! <a href="../login/">Go back</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
