<?php
/* === delete_attachment.php ===
 * AJAX Endpoint to delete an attachment.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = currentUserId();
    $att_id = $_POST['attachment_id'] ?? null;

    if ($att_id) {
        // Get file path first & Verify ownership via Task (or direct user_id if migrated)
        // Using JOIN to be safe if attachment user_id wasn't populated for some reason, 
        // relying on Task ownership.
        $sql = "SELECT a.task_id, a.file_path, a.file_name 
                FROM attachments a 
                JOIN tasks t ON a.task_id = t.id 
                WHERE a.id = ? AND t.user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $att_id, $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $att = mysqli_fetch_assoc($res);

        if ($att) {
            // Delete from DB
            $del_sql = "DELETE FROM attachments WHERE id = ?";
            $del_stmt = mysqli_prepare($conn, $del_sql);
            mysqli_stmt_bind_param($del_stmt, "i", $att_id);
            
            if (mysqli_stmt_execute($del_stmt)) {
                // Legacy cleanup
                if (!empty($att['file_path']) && file_exists('../' . $att['file_path'])) {
                    @unlink('../' . $att['file_path']);
                }
                
                log_action($conn, $att['task_id'], 'attachment_deleted', "Deleted: " . $att['file_name'], $user_id);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'DB Error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Attachment not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>
