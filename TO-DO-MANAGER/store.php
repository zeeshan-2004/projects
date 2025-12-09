<?php
/* === store.php ===
 * Handles creation of new tasks.
 */
session_start();
require_once 'db.php';

// Helper to convert DD/MM/YYYY to YYYY-MM-DD
function convert_date_to_db($dateStr) {
    if (empty($dateStr)) return null;
    // Check if it's already YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
    // Try DD/MM/YYYY
    $d = DateTime::createFromFormat('d/m/Y', $dateStr);
    return $d ? $d->format('Y-m-d') : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    
    // Convert Dates
    $start_date = !empty($_POST['start_date']) ? convert_date_to_db($_POST['start_date']) : null;
    $due_date = !empty($_POST['due_date']) ? convert_date_to_db($_POST['due_date']) : null;
    
    $status = !empty($_POST['status']) ? $_POST['status'] : 'pending';
    $priority = !empty($_POST['priority']) ? $_POST['priority'] : 'medium';

    // Validation
    if (empty($title)) {
        $_SESSION['flash_message'] = "Error: Title is required.";
        $_SESSION['flash_type'] = "error";
        header("Location: create.php");
        exit;
    }

    if ($start_date && $due_date && $start_date > $due_date) {
        $_SESSION['flash_message'] = "Error: Start Date cannot be after Due Date.";
        $_SESSION['flash_type'] = "error";
        header("Location: create.php");
        exit;
    }

    // Insert Task
    $sql = "INSERT INTO tasks (title, description, status, priority, category_id, start_date, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssiss", $title, $description, $status, $priority, $category_id, $start_date, $due_date);
        
        if (mysqli_stmt_execute($stmt)) {
            $task_id = mysqli_insert_id($conn);
            log_action($conn, $task_id, 'created', 'Task created');

            // Handle File Uploads
            if (!empty($_FILES['attachments']['name'][0])) {
                $upload_dir = 'uploads/attachments/';
                $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];
                $count = count($_FILES['attachments']['name']);

                for ($i = 0; $i < $count; $i++) {
                    $filename = $_FILES['attachments']['name'][$i];
                    $tmp_name = $_FILES['attachments']['tmp_name'][$i];
                    $size = $_FILES['attachments']['size'][$i];
                    $error = $_FILES['attachments']['error'][$i];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Check for upload errors
                    if ($error !== UPLOAD_ERR_OK) {
                        log_action($conn, $task_id, 'upload_error', "Error code: $error for file $filename");
                        continue; 
                    }

                    if (in_array($ext, $allowed)) {
                        if ($size <= 5242880) { // 5MB
                            $new_name = time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^a-z0-9.]/i', '_', $filename);
                            $dest_path = $upload_dir . $new_name;
                            
                            // Ensure directory exists
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }

                            if (move_uploaded_file($tmp_name, $dest_path)) {
                                $mime = get_file_mime_type($dest_path);
                                
                                $sql_att = "INSERT INTO attachments (task_id, file_name, file_path, mime, size) VALUES (?, ?, ?, ?, ?)";
                                $stmt_att = mysqli_prepare($conn, $sql_att);
                                mysqli_stmt_bind_param($stmt_att, "isssi", $task_id, $filename, $dest_path, $mime, $size);
                                mysqli_stmt_execute($stmt_att);
                                
                                log_action($conn, $task_id, 'attachment_added', "Uploaded: $filename");
                            } else {
                                log_action($conn, $task_id, 'upload_error', "Failed to move file: $filename");
                            }
                        } else {
                            log_action($conn, $task_id, 'upload_error', "File too large: $filename");
                        }
                    } else {
                        log_action($conn, $task_id, 'upload_error', "Invalid extension: $filename");
                    }
                }
            }

            $_SESSION['flash_message'] = "Task created successfully!";
            $_SESSION['flash_type'] = "success";
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['flash_message'] = "Database Error: " . mysqli_error($conn);
            $_SESSION['flash_type'] = "error";
            header("Location: create.php");
        }
    } else {
        $_SESSION['flash_message'] = "Statement Error: " . mysqli_error($conn);
        $_SESSION['flash_type'] = "error";
        header("Location: create.php");
    }
} else {
    header("Location: index.php");
}
?>
