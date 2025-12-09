<?php
/* === update.php ===
 * Handles updating of tasks.
 */
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
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

    // Update Task
    $sql = "UPDATE tasks SET title = ?, description = ?, status = ?, priority = ?, category_id = ?, start_date = ?, due_date = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    // ssssissi -> string * 4, int, string * 2, int
    mysqli_stmt_bind_param($stmt, "ssssissi", $title, $description, $status, $priority, $category_id, $start_date, $due_date, $id);

    if (mysqli_stmt_execute($stmt)) {
        log_action($conn, $id, 'updated', 'Task updated');

        // New Attachments
        if (!empty($_FILES['attachments']['name'][0])) {
            $upload_dir = 'uploads/attachments/';
            $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];
            $count = count($_FILES['attachments']['name']);

            for ($i = 0; $i < $count; $i++) {
                $filename = $_FILES['attachments']['name'][$i];
                $tmp_name = $_FILES['attachments']['tmp_name'][$i];
                $size = $_FILES['attachments']['size'][$i];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed) && $size <= 5242880) {
                    $new_name = time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^a-z0-9.]/i', '_', $filename);
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        $mime = get_file_mime_type($upload_dir . $new_name);
                        
                        $sql_att = "INSERT INTO attachments (task_id, file_name, file_path, mime, size) VALUES (?, ?, ?, ?, ?)";
                        $stmt_att = mysqli_prepare($conn, $sql_att);
                        $path_db = $upload_dir . $new_name;
                        mysqli_stmt_bind_param($stmt_att, "isssi", $id, $filename, $path_db, $mime, $size);
                        mysqli_stmt_execute($stmt_att);
                        
                        log_action($conn, $id, 'attachment_added', "Uploaded: $filename");
                    }
                }
            }
        }

        $_SESSION['flash_message'] = "Task updated successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['flash_message'] = "Error updating task: " . mysqli_error($conn);
        $_SESSION['flash_type'] = "error";
        header("Location: edit.php?id=" . $id);
    }
} else {
    header("Location: index.php");
}
?>
