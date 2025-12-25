<?php
require_once '../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } else {
        // Check email
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already registered.";
        }
        mysqli_stmt_close($stmt);

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed);
            
            if (mysqli_stmt_execute($stmt)) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $errors[] = "Error registering user.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - To Do Manager</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f4; }
        .auth-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 350px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;}
        .error { color: red; margin-bottom: 15px; font-size: 0.9em; }
        .btn-block { width: 100%; }
        h2 { text-align: center; margin-top: 0; }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2>Register</h2>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach($errors as $e) echo $e . "<br>"; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" required value="<?php echo isset($name) ? escape($name) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?php echo isset($email) ? escape($email) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p style="text-align:center; margin-top: 15px;">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </div>
</body>
</html>
