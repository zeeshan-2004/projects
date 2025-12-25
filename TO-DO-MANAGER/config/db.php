<?php
/*
 * === config/db.php ===
 * Database Connection & Global Config
 */

$host = 'localhost';
$user = 'root';
$pass = ''; 
$dbname = 'todo_manager';
$port = 3307;
$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Define Base Paths
define('BASE_PATH', dirname(__DIR__)); 
define('BASE_URL', '/projects/TO-DO-MANAGER');

// Start Session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';
?>
