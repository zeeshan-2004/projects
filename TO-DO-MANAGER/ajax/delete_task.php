<?php
/* === ajax/delete_task.php ===
 * AJAX Endpoint to delete a task.
 */
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        // Fetch attachments to delete physical files
        $att_sql = "SELECT file_path FROM attachments WHERE task_id = ?";
        $att_stmt = mysqli_prepare($conn, $att_sql);
        mysqli_stmt_bind_param($att_stmt, "i", $id);
        mysqli_stmt_execute($att_stmt);
        $result = mysqli_stmt_get_result($att_stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            if (file_exists('../' . $row['file_path'])) {
                unlink('../' . $row['file_path']);
            }
        }

        // Delete Task (Cascade deletes attachments/logs in logic or DB)
        // Note: DB Upgrade has ON DELETE CASCADE for attachments/logs?
        // Logs has ON DELETE SET NULL. Attachments ON DELETE CASCADE.
        
        $sql = "DELETE FROM tasks WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            log_action($conn, null, 'deleted', "Task ID $id deleted"); // task_id is gone, so null or user log
            // Actually log might fail if task_id fk constraint exists? 
            // Logs FK is ON DELETE SET NULL, so the log for 'deleted' should probably have task_id NULL or we log BEFORE delete?
            // If we log AFTER delete with task_id $id, it becomes NULL.
            // But we typically want to record "Task deleted". 
            // Let's Insert log with task_id NULL explicitly.
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB Error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>
