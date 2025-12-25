<?php
/* === store.php ===
 * Handles creation of new tasks.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = currentUserId();
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

    // Insert Task with User ID
    $sql = "INSERT INTO tasks (user_id, title, description, status, priority, category_id, start_date, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssisss", $user_id, $title, $description, $status, $priority, $category_id, $start_date, $due_date);
        // Correct types: i (user), s (title), s (desc), s (status), s (priority), i (cat), s (start), s (due)
        // Oops, bind param string must match.
        // user_id=i, title=s, desc=s, status=s, priority=s, cat_id=i (nullable?), start=s, due=s.
        // If cat_id is null, types still fixed.
        // Let's use 'isssisss' ? Wait, cat_id is integer.
        // New bind: "isssiss" (Wait, 8 params).
        // user(i), title(s), desc(s), status(s), priority(s), cat(i), start(s), due(s). -> "isssiss" is 7. "isssisss" is 8?
        // Count: 1,2,3,4,5,6,7,8.
        // "isssisss".
        mysqli_stmt_bind_param($stmt, "isssisss", $user_id, $title, $description, $status, $priority, $category_id, $start_date, $due_date);
        
        if (mysqli_stmt_execute($stmt)) {
            $task_id = mysqli_insert_id($conn);
            log_action($conn, $task_id, 'created', 'Task created', $user_id);

            // Handle File Uploads
            if (!empty($_FILES['attachments']['name'][0])) {
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
                        log_action($conn, $task_id, 'upload_error', "Error code: $error for file $filename", $user_id);
                        continue; 
                    }

                    if (in_array($ext, $allowed)) {
                        if ($size <= 16777216) { 
                            // Read file content
                            $file_content = file_get_contents($tmp_name);
                            $mime = get_file_mime_type($tmp_name); 
                            $null_path = null; 

                            // Insert into DB with user_id
                            $sql_att = "INSERT INTO attachments (user_id, task_id, file_name, file_path, file_content, mime, size) VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $stmt_att = mysqli_prepare($conn, $sql_att);
                            // user(i), task(i), name(s), path(s), content(b), mime(s), size(i).
                            // "iissssi" (Actually content is blob, send_long better, but using 's' for simple).
                            // Let's use 'iissssi'.
                            mysqli_stmt_bind_param($stmt_att, "iissssi", $user_id, $task_id, $filename, $null_path, $null, $mime, $size);
                            mysqli_stmt_send_long_data($stmt_att, 4, $file_content); // 0-indexed index 4 is file_content? 
                            // user(0), task(1), name(2), path(3), content(4). Yes.
                            
                            if (mysqli_stmt_execute($stmt_att)) {
                                log_action($conn, $task_id, 'attachment_added', "Uploaded to DB: $filename", $user_id);
                            } else {
                                log_action($conn, $task_id, 'upload_error', "DB Error: " . mysqli_error($conn), $user_id);
                            }
                        } else {
                            log_action($conn, $task_id, 'upload_error', "File too large: $filename", $user_id);
                        }
                    } else {
                        log_action($conn, $task_id, 'upload_error', "Invalid extension: $filename", $user_id);
                    }
                }
            }

            $_SESSION['flash_message'] = "Task created successfully!";
            $_SESSION['flash_type'] = "success";
            header("Location: ../index.php");
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
    header("Location: ../index.php");
}
?>
