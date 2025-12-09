<?php
/* === ajax/delete_attachment.php ===
 * AJAX Endpoint to delete an attachment.
 */
require_once '../db.php'; // adjusting path since inside ajax/

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $att_id = $_POST['attachment_id'] ?? null;

    if ($att_id) {
        // Get file path first
        $sql = "SELECT task_id, file_path, file_name FROM attachments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $att_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $att = mysqli_fetch_assoc($res);

        if ($att) {
            // Delete from DB
            $del_sql = "DELETE FROM attachments WHERE id = ?";
            $del_stmt = mysqli_prepare($conn, $del_sql);
            mysqli_stmt_bind_param($del_stmt, "i", $att_id);
            
            if (mysqli_stmt_execute($del_stmt)) {
                // Remove file
                if (file_exists('../' . $att['file_path'])) {
                    unlink('../' . $att['file_path']);
                }
                
                log_action($conn, $att['task_id'], 'attachment_deleted', "Deleted: " . $att['file_name']);
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
