<?php
/* === upgrade_features.php ===
 * Script to apply the feature upgrade SQL.
 */
require_once 'db.php';

$sql_file = 'sql/feature_upgrade.sql';

if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    
    // Remove comments
    $sql_content = preg_replace('/^--.*$/m', '', $sql_content); // Single line comments --
    $sql_content = preg_replace('/^\#.*$/m', '', $sql_content); // Single line comments #
    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content); // Multi line comments /* */

    $queries = explode(';', $sql_content);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                if (mysqli_query($conn, $query)) {
                    echo "Executed: " . substr(str_replace("\n", " ", $query), 0, 50) . "...\n";
                } else {
                    $err = mysqli_error($conn);
                    if (strpos($err, 'Duplicate column') !== false || strpos($err, 'already exists') !== false) {
                        echo "Skipping (already exists): " . substr(str_replace("\n", " ", $query), 0, 30) . "...\n";
                    } else {
                         echo "Error: " . $err . "\nQuery: " . $query . "\n";
                    }
                }
            } catch (Exception $e) {
                 echo "Exception: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "Feature upgrade completed.\n";
} else {
    echo "SQL file not found.\n";
}
?>
