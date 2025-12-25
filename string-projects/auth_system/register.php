<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$errors = [];
$username = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    $usernameError = validateUsername($username);
    if ($usernameError) {
        $errors[] = $usernameError;
    }

    $passwordError = validatePassword($password);
    if ($passwordError) {
        $errors[] = $passwordError;
    }

    // Check for duplicate username if no validation errors so far
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username is already taken.";
        }
        $stmt->close();
    }

    // Register user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            $success = true;
            // setFlashMessage('success', "Registration successful! Please login."); // Optional: Use session flash
            // header("Location: login.php"); // Optional: Redirect immediately
            // exit();
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <h2>Register</h2>

    <?php if ($success): ?>
        <div class="success">Registration successful! <a href="login.php">Login here</a>.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            <small>5-50 chars, letters & numbers only.</small>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <small>Min 8 chars, 1 upper, 1 lower, 1 number, 1 special.</small>
        </div>
        <button type="submit" class="btn">Register</button>
    </form>
    <div class="links">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

</body>
</html>
