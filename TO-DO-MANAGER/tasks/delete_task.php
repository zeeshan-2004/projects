<?php
/* === delete_task.php ===
 * AJAX Endpoint to delete a task.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = currentUserId();
    $id = $_POST['id'] ?? null;

    if ($id) {
        // Verify ownership first
        $check_sql = "SELECT id FROM tasks WHERE id = ? AND user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($check_stmt);
        if (!mysqli_stmt_fetch($check_stmt)) {
             echo json_encode(['success' => false, 'message' => 'Task not found or permission denied']);
             exit;
        }
        mysqli_stmt_close($check_stmt);

        // Fetch attachments to delete physical files
        $att_sql = "SELECT file_path FROM attachments WHERE task_id = ?";
        $att_stmt = mysqli_prepare($conn, $att_sql);
        mysqli_stmt_bind_param($att_stmt, "i", $id);
        mysqli_stmt_execute($att_stmt);
        $result = mysqli_stmt_get_result($att_stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['file_path'] && file_exists('../' . $row['file_path'])) {
                unlink('../' . $row['file_path']);
            }
        }

        // Delete Task (Scoped)
        $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            // Log manually if task is gone but we want record
            // We can pass user_id to log_action
            log_action($conn, null, 'deleted', "Task ID $id deleted", $user_id);
            
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
