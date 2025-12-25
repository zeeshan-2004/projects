<?php
// config/db.php

$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$dbname = 'student_results';
$port = 3307;

$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
