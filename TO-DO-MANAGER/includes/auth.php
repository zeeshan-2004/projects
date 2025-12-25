<?php
/* === includes/auth.php === */

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Handle redirect based on current location
        // If we are in subfolder (e.g. tasks/), go up. 
        // Simple way: Use BASE_URL
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
}

function currentUserId() {
    return $_SESSION['user_id'] ?? 0;
}
?>
