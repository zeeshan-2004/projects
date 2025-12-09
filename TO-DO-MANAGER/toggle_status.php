<?php
/* === toggle_status.php ===
 * Toggles task status: pending -> in_progress -> completed -> pending
 */
session_start();
require_once 'db.php';

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

            if ($current === 'pending') {
                $new_status = 'in_progress';
            } elseif ($current === 'in_progress') {
                $new_status = 'completed';
            } else {
                // completed -> pending
                $new_status = 'pending';
            }

            // Update status
            $update_sql = "UPDATE tasks SET status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $id);

            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['flash_message'] = "Task marked as " . ucwords(str_replace('_', ' ', $new_status)) . ".";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Error updating status: " . mysqli_error($conn);
                $_SESSION['flash_type'] = "error";
            }
        } else {
            $_SESSION['flash_message'] = "Task not found.";
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
