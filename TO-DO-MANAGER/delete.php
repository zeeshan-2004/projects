<?php
/* === delete.php ===
 * Fallback delete script (Non-AJAX).
 */
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        // Delete attachments
        $att_sql = "SELECT file_path FROM attachments WHERE task_id = ?";
        $att_stmt = mysqli_prepare($conn, $att_sql);
        mysqli_stmt_bind_param($att_stmt, "i", $id);
        mysqli_stmt_execute($att_stmt);
        $result = mysqli_stmt_get_result($att_stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            if (file_exists($row['file_path'])) {
                unlink($row['file_path']);
            }
        }

        $sql = "DELETE FROM tasks WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['flash_message'] = "Task deleted successfully.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error deleting task: " . mysqli_error($conn);
            $_SESSION['flash_type'] = "error";
        }
    } else {
        $_SESSION['flash_message'] = "Invalid task ID.";
        $_SESSION['flash_type'] = "error";
    }
}

header("Location: index.php");
exit;
?>
