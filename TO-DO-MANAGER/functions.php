<?php
/* === functions.php ===
 * Helper functions for To Do Manager.
 */

// Escape HTML output
function escape($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Format date
function format_date($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

// Helper to convert DD/MM/YYYY to YYYY-MM-DD
function convert_date_to_db($dateStr) {
    if (empty($dateStr)) return null;
    // Check if it's already YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
    // Try DD/MM/YYYY
    $d = DateTime::createFromFormat('d/m/Y', $dateStr);
    return $d ? $d->format('Y-m-d') : null;
}

// Log action to database
function log_action($conn, $task_id, $action, $details = null, $user_id = null) {
    // $details can be a string or array (convert to JSON)
    if (is_array($details)) {
        $details = json_encode($details);
    }
    
    $sql = "INSERT INTO activity_logs (task_id, user_id, action, details) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iiss", $task_id, $user_id, $action, $details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Check if task is overdue
function is_overdue($due_date, $status) {
    if (!$due_date || $status === 'completed') return false;
    return (date('Y-m-d') > $due_date);
}

// Get MIME type manually (fallback for missing fileinfo extension)
function get_file_mime_type($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'txt' => 'text/plain'
    ];
    return $mimes[$ext] ?? 'application/octet-stream';
}
?>
