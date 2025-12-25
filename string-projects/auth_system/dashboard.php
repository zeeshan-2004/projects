<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require login to access this page
requireLogin();

// Check if username is in session, if not, fetch from DB
if (!isset($_SESSION['username'])) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            $_SESSION['username'] = $user['username'];
        } else {
            // User ID in session not found in DB
            header("Location: logout.php");
            exit();
        }
        $stmt->close();
    }
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
    <p style="text-align: center; color: #555;">You have successfully logged in.</p>
    
    <div style="margin-top: 30px;">
        <a href="logout.php" class="btn btn-danger" style="display: block; text-align: center; text-decoration: none;">Logout</a>
    </div>
</div>

</body>
</html>
