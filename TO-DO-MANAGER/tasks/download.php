<?php
/* === download.php ===
 * Serves files from the database.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $user_id = currentUserId();
    
    // Check ownership via Task
    $sql = "SELECT a.file_name, a.file_content, a.mime, a.size, a.file_path 
            FROM attachments a 
            JOIN tasks t ON a.task_id = t.id 
            WHERE a.id = ? AND t.user_id = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    $stmt->store_result();
    $stmt->bind_result($file_name, $file_content, $mime, $size, $file_path);
    
    if ($stmt->fetch()) {
        if ($file_content) {
            // Serve from DB
            header("Content-Type: " . $mime);
            header("Content-Length: " . $size);
            header("Content-Disposition: inline; filename=\"" . $file_name . "\"");
            echo $file_content;
        } elseif (file_exists($file_path)) {
             // Fallback to file system if content is empty (legacy files)
            header("Content-Type: " . $mime);
            header("Content-Length: " . $size);
            header("Content-Disposition: inline; filename=\"" . $file_name . "\"");
            readfile($file_path);
        } else {
            echo "File not found.";
        }
    } else {
        echo "Attachment not found.";
    }
} else {
    echo "Invalid Request.";
}
?>
