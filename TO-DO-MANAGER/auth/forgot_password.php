<?php
require_once '../config/db.php';

$message = '';
$debugLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Attempt to create reset token
        // Security: Always show same message to prevent email enumeration
        $token = create_password_reset($conn, $email);
        
        if ($token) {
            $link = send_password_reset_email($email, $token);
            // FOR DEV/LOCALHOST ONLY:
            $debugLink = $link; 
        }
        
        $message = "If an account exists with this email, a password reset link has been sent to it.";
    } else {
        $message = "Please enter a valid email address.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - To Do Manager</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f4; }
        .auth-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 350px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .message { color: green; margin-bottom: 15px; font-size: 0.9em; text-align: center; }
        .btn-block { width: 100%; }
        h2 { text-align: center; margin-top: 0; }
        .debug-box { background: #ffeb3b; padding: 10px; margin-bottom: 15px; font-size: 0.8em; word-break: break-all; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2>Forgot Password</h2>
        
        <?php if ($debugLink): ?>
            <div class="debug-box">
                <strong>(Localhost Mode)</strong><br>
                Link: <a href="<?php echo $debugLink; ?>">Reset Password</a>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Enter your email address</label>
                <input type="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
        </form>
        <p style="text-align:center; margin-top: 15px;">
            <a href="login.php">Back to Login</a>
        </p>
    </div>
</body>
</html>
