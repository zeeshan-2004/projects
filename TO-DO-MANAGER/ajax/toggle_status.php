<?php
/* === ajax/toggle_status.php ===
 * AJAX Endpoint to toggle status.
 */
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        $sql = "SELECT status FROM tasks WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $task = mysqli_fetch_assoc($result);

        if ($task) {
            $current = $task['status'];
            $new_status = 'pending';

            if ($current === 'pending') $new_status = 'in_progress';
            elseif ($current === 'in_progress') $new_status = 'completed';
            else $new_status = 'pending';

            $update_sql = "UPDATE tasks SET status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $id);

            if (mysqli_stmt_execute($update_stmt)) {
                log_action($conn, $id, 'status_changed', json_encode(['old' => $current, 'new' => $new_status]));
                
                $label = ucwords(str_replace('_', ' ', $new_status));
                $class = 'status-' . str_replace('_', '-', $new_status);
                
                echo json_encode([
                    'success' => true, 
                    'new_status' => $new_status, 
                    'label' => $label,
                    'badge_class' => $class
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'DB Error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Task not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>
