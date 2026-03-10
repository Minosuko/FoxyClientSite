<?php
require_once __DIR__ . '/../../includes/db.php';

$title = "Account Verification";
$error = "";
$success = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find user with this token
    $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        // Verify the user
        $stmt = $mysqli->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        
        if ($stmt->execute()) {
            $success = "Welcome, " . htmlspecialchars($user['username']) . "! Your account has been verified successfully. You can now <a href='../login/'>login</a>.";
        } else {
            $error = "Verification failed. Please try again later.";
        }
    } else {
        $error = "Invalid or expired verification token.";
    }
} else {
    $error = "No verification token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account | Foxy Client</title>
    <link rel="stylesheet" href="../auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Account Verification</h2>
            <p>Activate your Foxy Client account</p>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-top: 20px;"><?php echo $error; ?></div>
                <div class="auth-footer" style="margin-top: 20px;">
                    Having trouble? <a href="../../#support">Contact Support</a>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-top: 20px;"><?php echo $success; ?></div>
                <div class="auth-footer" style="margin-top: 20px;">
                    Go to <a href="../login/">Login Page</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
