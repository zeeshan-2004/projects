<?php
/*
 * check_db_columns.php
 * Checks if start_date and due_date exist in tasks table.
 */
require_once 'db.php';

echo "Checking columns in table 'tasks'...\n";

$result = mysqli_query($conn, "SHOW COLUMNS FROM tasks");

$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

$missing = [];
if (!in_array('start_date', $columns)) $missing[] = 'start_date';
if (!in_array('due_date', $columns)) $missing[] = 'due_date';

// Check status column type
$status_row = mysqli_fetch_assoc(mysqli_query($conn, "SHOW COLUMNS FROM tasks WHERE Field = 'status'"));
echo "Status column type: " . $status_row['Type'] . "\n";

if (empty($missing)) {
    echo "All columns found.\n";
} else {
    echo "Missing columns: " . implode(', ', $missing) . "\n";
    
    // Attempt to fix
    foreach ($missing as $col) {
        echo "Adding $col...\n";
        $sql = "ALTER TABLE tasks ADD COLUMN $col DATE NULL AFTER description"; 
        // Note: AFTER description is just a guess, specific positioning isn't critical
        if (mysqli_query($conn, $sql)) {
            echo "Added $col successfully.\n";
        } else {
            echo "Error adding $col: " . mysqli_error($conn) . "\n";
        }
    }
}

// Ensure status is updated
$sql_status = "ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending'";
if (mysqli_query($conn, $sql_status)) {
    echo "Status column updated/verified.\n";
} else {
    echo "Error updating status column: " . mysqli_error($conn) . "\n";
}

?>
