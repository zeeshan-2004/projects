<?php
// setup_db.php

$host = '127.0.0.1';
$user = 'root';
$pass = ''; 
$port = 3307;

// 1. Connect without Database
$conn = mysqli_connect($host, $user, $pass, '', $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 2. Create Database
$sql = "CREATE DATABASE IF NOT EXISTS student_results";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully.<br>";
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// 3. Select Database
mysqli_select_db($conn, 'student_results');

// 4. Run Schema from file
$schemaFile = __DIR__ . '/database/schema.sql';
if (file_exists($schemaFile)) {
    $sqlContent = file_get_contents($schemaFile);
    
    $queries = explode(';', $sqlContent);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if (mysqli_query($conn, $query)) {
                // Success
            } else {
                echo "Error executing query: " . mysqli_error($conn) . "<br>";
            }
        }
    }
    echo "Tables and data imported successfully.<br>";
} else {
    echo "Schema file not found.<br>";
}

echo "Setup complete. <a href='index.php'>Go to Dashboard</a>";
?>
