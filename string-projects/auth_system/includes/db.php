<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$dbname = 'auth_system';

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // If database doesn't exist, try connecting without it and check
    if ($e->getCode() == 1049) { // Unknown database
        die("Database '$dbname' not found. Please import 'database.sql' first.");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
