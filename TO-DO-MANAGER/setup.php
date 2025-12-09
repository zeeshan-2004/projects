<?php
/* === setup.php ===
 * Script to initialize the database and tables for the To Do Manager.
 * Run this once.
 */

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is empty

// 1. Connect to MySQL Server
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}
echo "Connected to MySQL server.\n";

// 2. Create Database
$dbname = 'todo_manager';
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    echo "Database '$dbname' checked/created successfully.\n";
} else {
    die("Error creating database: " . mysqli_error($conn) . "\n");
}

// 3. Select Database
mysqli_select_db($conn, $dbname);

// 4. Create Categories Table
$sql_cats = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
)";
if (mysqli_query($conn, $sql_cats)) {
    echo "Table 'categories' checked/created.\n";
} else {
    echo "Error creating table 'categories': " . mysqli_error($conn) . "\n";
}

// 5. Insert Default Categories (if empty)
$check_cats = mysqli_query($conn, "SELECT count(*) as count FROM categories");
$row = mysqli_fetch_assoc($check_cats);
if ($row['count'] == 0) {
    $sql_insert = "INSERT INTO categories (name) VALUES ('Development'), ('Study'), ('Personal'), ('Work')";
    if (mysqli_query($conn, $sql_insert)) {
        echo "Default categories inserted.\n";
    } else {
        echo "Error inserting categories: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Categories already exist, skipping default insert.\n";
}

// 6. Create Tasks Table (with category_id)
$sql_tasks = "CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    category_id INT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";
if (mysqli_query($conn, $sql_tasks)) {
    echo "Table 'tasks' checked/created.\n";
} else {
    echo "Error creating table 'tasks': " . mysqli_error($conn) . "\n";
}

// 7. Check if tasks table needs upgrade (if it existed before categories)
// This handles the case where tasks existed but category_id column didn't.
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM tasks LIKE 'category_id'");
if (mysqli_num_rows($check_col) == 0) {
    echo "Upgrading tasks table to include category_id...\n";
    $sql_alter = "ALTER TABLE tasks ADD COLUMN category_id INT NULL,
                  ADD CONSTRAINT fk_task_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
    if (mysqli_query($conn, $sql_alter)) {
        echo "Tasks table upgraded successfully.\n";
    } else {
        echo "Error upgrading tasks table: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
echo "\nSetup completed successfully! You can now access the project.";
?>
