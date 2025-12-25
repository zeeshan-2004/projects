<?php
/* === update.php ===
 * Handles updating of tasks.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = currentUserId();
    $id = $_POST['id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $start_date = !empty($_POST['start_date']) ? convert_date_to_db($_POST['start_date']) : null;
    $due_date = !empty($_POST['due_date']) ? convert_date_to_db($_POST['due_date']) : null;
    $status = $_POST['status'];
    $priority = $_POST['priority'];

    if (empty($id) || empty($title)) {
        $_SESSION['flash_message'] = "Error: Title is required.";
        $_SESSION['flash_type'] = "error";
        header("Location: edit.php?id=" . $id);
        exit;
    }

    if ($start_date && $due_date && $start_date > $due_date) {
        $_SESSION['flash_message'] = "Error: Start Date cannot be after Due Date.";
        $_SESSION['flash_type'] = "error";
        header("Location: edit.php?id=" . $id);
        exit;
    }

    // Update Task (Scoped to User)
    $sql = "UPDATE tasks SET title = ?, description = ?, status = ?, priority = ?, category_id = ?, start_date = ?, due_date = ? WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    // ssssissi i -> 9 params
    mysqli_stmt_bind_param($stmt, "ssssissii", $title, $description, $status, $priority, $category_id, $start_date, $due_date, $id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        // Check if any row was affected (optional, but good for verification)
        if (mysqli_stmt_affected_rows($stmt) > 0 || mysqli_errno($conn) == 0) {
            log_action($conn, $id, 'updated', 'Task updated', $user_id);
            
            // New Attachments
            if (!empty($_FILES['attachments']['name'][0])) {
                $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];
                $count = count($_FILES['attachments']['name']);

                for ($i = 0; $i < $count; $i++) {
                    $filename = $_FILES['attachments']['name'][$i];
                    $tmp_name = $_FILES['attachments']['tmp_name'][$i];
                    $size = $_FILES['attachments']['size'][$i];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed) && $size <= 16777216) {
                        $file_content = file_get_contents($tmp_name);
                        $mime = get_file_mime_type($tmp_name);
                        $null_path = null;
                        
                        // Insert Attachment with User ID
                        $sql_att = "INSERT INTO attachments (user_id, task_id, file_name, file_path, file_content, mime, size) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt_att = mysqli_prepare($conn, $sql_att);
                        // iissssi
                        mysqli_stmt_bind_param($stmt_att, "iissssi", $user_id, $id, $filename, $null_path, $null, $mime, $size);
                        mysqli_stmt_send_long_data($stmt_att, 4, $file_content);
                        
                        if (mysqli_stmt_execute($stmt_att)) {
                            log_action($conn, $id, 'attachment_added', "Uploaded to DB: $filename", $user_id);
                        }
                    }
                }
            }

            $_SESSION['flash_message'] = "Task updated successfully!";
            $_SESSION['flash_type'] = "success";
            header("Location: ../index.php");
            exit;
        } else {
            $_SESSION['flash_message'] = "Task not found or permission denied.";
            $_SESSION['flash_type'] = "error";
            header("Location: edit.php?id=" . $id);
        }
    } else {
        $_SESSION['flash_message'] = "Error updating task: " . mysqli_error($conn);
        $_SESSION['flash_type'] = "error";
        header("Location: edit.php?id=" . $id);
    }
} else {
    header("Location: ../index.php");
}
?>
