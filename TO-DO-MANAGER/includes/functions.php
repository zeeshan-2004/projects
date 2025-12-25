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

// === Password Reset Functions ===

// Generate a random token
function generate_reset_token() {
    return bin2hex(random_bytes(32)); // 64 chars
}

// Hash the token for storage
function hash_reset_token($token) {
    return hash('sha256', $token);
}

// Create and store a reset token for a user
function create_password_reset($conn, $email) {
    // 1. Check if user exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$user) {
        // Return false or null but controller should act effectively same to avoid enumeration
        // For simplicity here, we return false so controller knows not to send email
        return false;
    }

    $user_id = $user['id'];
    $token = generate_reset_token();
    $token_hash = hash_reset_token($token);
    $expires_at = date('Y-m-d H:i:s', time() + 1800); // 30 minutes from now

    // 2. Delete existing tokens for this user
    $delStmt = mysqli_prepare($conn, "DELETE FROM password_reset_tokens WHERE user_id = ?");
    mysqli_stmt_bind_param($delStmt, "i", $user_id);
    mysqli_stmt_execute($delStmt);
    mysqli_stmt_close($delStmt);

    // 3. Insert new token
    $insStmt = mysqli_prepare($conn, "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insStmt, "iss", $user_id, $token_hash, $expires_at);
    $success = mysqli_stmt_execute($insStmt);
    mysqli_stmt_close($insStmt);

    return $success ? $token : false;
}

// Verify a token
function verify_reset_token($conn, $token) {
    $token_hash = hash_reset_token($token);
    $current_time = date('Y-m-d H:i:s');
    
    $sql = "SELECT * FROM password_reset_tokens 
            WHERE token_hash = ? AND expires_at > ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $token_hash, $current_time);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $record = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    
    return $record; // Returns row or null
}

// Delete token after use
function delete_reset_token($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM password_reset_tokens WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Mock Email Function (Localhost)
function send_password_reset_email($email, $token) {
    $resetLink = BASE_URL . "/auth/reset_password.php?token=" . $token . "&email=" . urlencode($email);
    
    // For production with SMTP, you would use PHPMailer here.
    // For localhost, we will mock it by logging and displaying it.
    
    $subject = "Password Reset Request";
    $message = "Click here to reset your password: " . $resetLink;
    
    // Log to a file for easy access
    $logFile = dirname(__DIR__) . '/reset_emails.log';
    $logEntry = "[" . date('Y-m-d H:i:s') . "] To: $email | Link: $resetLink" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // In a real app, do not echo this. But for localhost/this specific request:
    return $resetLink; 
}
?>
