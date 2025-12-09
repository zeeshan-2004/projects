<?php
/* === verify_features_db.php ===
 * Checks if the major feature upgrade database changes are present.
 */
require_once 'db.php';

$missing = [];

// 1. Check 'priority' column in 'tasks'
$res = mysqli_query($conn, "SHOW COLUMNS FROM tasks LIKE 'priority'");
if (mysqli_num_rows($res) == 0) {
    $missing[] = "Column 'priority' in 'tasks'";
}

// 2. Check 'activity_logs' table
$res = mysqli_query($conn, "SHOW TABLES LIKE 'activity_logs'");
if (mysqli_num_rows($res) == 0) {
    $missing[] = "Table 'activity_logs'";
}

// 3. Check 'attachments' table
$res = mysqli_query($conn, "SHOW TABLES LIKE 'attachments'");
if (mysqli_num_rows($res) == 0) {
    $missing[] = "Table 'attachments'";
}

if (!empty($missing)) {
    echo "MISSING FEATURES:\n";
    foreach ($missing as $m) echo "- $m\n";
    echo "Running upgrade script...\n";
    
    // Include the upgrade logic directly or call the script
    // We'll call the script to reuse logic
    include 'upgrade_features.php';
} else {
    echo "ALL FEATURES PRESENT.\n";
    echo "- priority column exists.\n";
    echo "- activity_logs table exists.\n";
    echo "- attachments table exists.\n";
}
?>
