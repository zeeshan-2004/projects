<?php
/* === upgrade_db.php ===
 * Script to apply the date and status upgrade.
 */
require_once 'db.php';

$sql_file = 'sql/date_status_upgrade.sql';

if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    
    // Split by semicolon to handle multiple statements
    $queries = explode(';', $sql_content);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && strpos($query, '--') === false) {
            if (mysqli_query($conn, $query)) {
                echo "Executed: " . substr($query, 0, 50) . "...\n";
            } else {
                // Ignore "Duplicate column" checks if rerunning, but print error otherwise
                if (strpos(mysqli_error($conn), 'Duplicate column') === false) {
                     echo "Error: " . mysqli_error($conn) . "\n";
                } else {
                    echo "Column already exists/Change already applied.\n";
                }
            }
        }
    }
    echo "Database upgrade completed.\n";
} else {
    echo "SQL file not found.\n";
}
?>
