<?php
/*
 * === db.php ===
 * Database Connection File.
 * Connects to MySQL using procedural mysqli.
 */

$host = 'localhost';
$user = 'root';
$pass = ''; // Adjust if you have a password
$dbname = 'todo_manager';

// Connect to database
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

require_once 'functions.php';
?>
