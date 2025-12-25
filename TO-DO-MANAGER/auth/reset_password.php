<?php
require_once '../config/db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Validate token presence
if (!$token) {
    die("Invalid request."); // Or redirect to login
}

// 1. Verify token
// Note: verify_reset_token() does the hash check and expiry check
$tokenData = verify_reset_token($conn, $token);

if (!$tokenData) {
    $error = "Invalid or expired password reset link.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // 2. Hash new password
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $user_id = $tokenData['user_id'];

        // 3. Update User Password
        $updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($updateStmt, "si", $new_hash, $user_id);
        
        if (mysqli_stmt_execute($updateStmt)) {
            // 4. Delete the used token
            delete_reset_token($conn, $user_id);
            
            // Redirect or show success
            header("Location: login.php?reset=success");
            exit;
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - To Do Manager</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f4; }
        .auth-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 350px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .error { color: red; margin-bottom: 15px; font-size: 0.9em; }
        .btn-block { width: 100%; }
        h2 { text-align: center; margin-top: 0; }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2>Reset Password</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
            <!-- Optional: Link back to forgot password if token invalid -->
             <p style="text-align:center;"><a href="forgot_password.php">Request new link</a></p>
        <?php elseif ($tokenData): ?>
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Set New Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
