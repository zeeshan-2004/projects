<?php

// Function to sanitize user input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate username
// Rules: 5-50 chars, letters & numbers only, no spaces
function validateUsername($username) {
    if (empty($username)) {
        return "Username is required.";
    }
    if (strlen($username) < 5 || strlen($username) > 50) {
        return "Username must be between 5 and 50 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        return "Username must contain only letters and numbers (no spaces).";
    }
    return null; // Valid
}

// Function to validate password
// Rules: Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
function validatePassword($password) {
    if (empty($password)) {
        return "Password is required.";
    }
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number.";
    }
    if (!preg_match('/[\W_]/', $password)) { // \W matches any non-word character (equivalent to [^a-zA-Z0-9_])
        return "Password must contain at least one special character.";
    }
    return null; // Valid
}

// Function to set flash messages (error/success)
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

// Function to get and clear flash messages
function getFlashMessage($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}
?>
