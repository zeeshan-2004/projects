<?php
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS contact_manager";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select database
if (mysqli_select_db($conn, "contact_manager")) {
    // Create table (matches database.sql)
    $sql = "CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL
    )";

    if (mysqli_query($conn, $sql)) {
        echo "Table 'contacts' created successfully or already exists.<br>";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Error selecting database: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?>
