<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/cf-turnstile.php';

$error = "";
$success = "";
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : "");

if (empty($token)) {
    $error = "No reset token provided.";
} else {
    // Validate token and check expiration
    $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        $error = "This reset link is invalid or has expired.";
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($password)) {
                $error = "Please enter a new password.";
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters.";
            } elseif ($password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
				if (!validateTurnstile($turnstile_secret_key)) {
					$resp = ['success' => false, 'error' => 'Invalid captcha'];
				} else {
					// Update password and clear token
					$password_hash = password_hash($password, PASSWORD_DEFAULT);
					$stmt = $mysqli->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
					$stmt->bind_param("si", $password_hash, $user['id']);
					
					if ($stmt->execute()) {
						$success = "Success! Your password has been updated. You can now <a href='../login/'>login</a> with your new password.";
					} else {
						$error = "Failed to update password. Please try again later.";
					}
				}
			}
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Foxy Client</title>
    <link rel="stylesheet" href="../auth.css">
	<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Reset Password</h2>
            <p>Enter a new secret for your account</p>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-top: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-top: 20px;"><?php echo $success; ?></div>
                <div class="auth-footer" style="margin-top: 20px;">
                    Go to <a href="../login/">Login Page</a>
                </div>
            <?php elseif (!$error || isset($_POST['password'])): ?>
                <form action="" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" required placeholder="At least 6 characters">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm your new password">
                    </div>
					<div class="cf-turnstile" data-sitekey="<?php echo $turnstile_site_key;?>"></div>
                    <button type="submit" class="btn btn-primary btn-auth">UPDATE PASSWORD</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
