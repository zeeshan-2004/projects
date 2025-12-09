<?php
/* === edit.php ===
 * Form to edit an existing task.
 */
session_start();
require_once 'db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Fetch task
$sql = "SELECT * FROM tasks WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$task = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$task) {
    header("Location: index.php");
    exit;
}

// Fetch categories
$cats_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");

// Fetch attachments
$att_sql = "SELECT * FROM attachments WHERE task_id = ?";
$stmt_att = mysqli_prepare($conn, $att_sql);
mysqli_stmt_bind_param($stmt_att, "i", $id);
mysqli_stmt_execute($stmt_att);
$attachments = mysqli_stmt_get_result($stmt_att);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        // AJAX to delete attachment
        function deleteAttachment(attId, btn) {
            if (!confirm('Delete this file?')) return;
            
            fetch('ajax/delete_attachment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'attachment_id=' + attId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.closest('.attachment-item').remove();
                } else {
                    alert('Error: ' + (data.message || 'Failed'));
                }
            })
            .catch(err => alert('Error deleting file'));
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Edit Task</h1>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?php echo escape($_SESSION['flash_type']); ?>">
                <?php 
                echo escape($_SESSION['flash_message']); 
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                ?>
            </div>
        <?php endif; ?>

        <form action="update.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo escape($task['id']); ?>">

            <div class="form-group">
                <label for="title">Title <span style="color:red">*</span></label>
                <input type="text" name="title" id="title" required value="<?php echo escape($task['title']); ?>">
            </div>
            
             <div class="form-group">
                <label for="category">Category</label>
                <select name="category_id" id="category">
                    <option value="">-- Select Category --</option>
                    <?php while($cat = mysqli_fetch_assoc($cats_result)): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $task['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo escape($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" style="display: flex; gap: 20px;">
                 <div style="flex: 1;">
                    <label for="priority">Priority</label>
                    <select name="priority" id="priority">
                        <option value="low" <?php echo $task['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $task['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $task['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
            </div>

             <div class="form-group" style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo $task['start_date']; ?>">
                </div>
                <div style="flex: 1;">
                    <label for="due_date">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="<?php echo $task['due_date']; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Attachments</label>
                <div class="attachment-list">
                    <?php while($att = mysqli_fetch_assoc($attachments)): ?>
                        <div class="attachment-item">
                            <span>
                                <a href="<?php echo escape($att['file_path']); ?>" target="_blank">
                                    <?php echo escape($att['file_name']); ?>
                                </a>
                                <small>(<?php echo round($att['size']/1024, 1); ?> KB)</small>
                            </span>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteAttachment(<?php echo $att['id']; ?>, this)">Delete</button>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div style="margin-top: 10px;">
                    <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.docx">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="5"><?php echo escape($task['description']); ?></textarea>
            </div>

             <div style="text-align: right; margin-bottom: 10px;">
                <a href="logs.php?task_id=<?php echo $task['id']; ?>" target="_blank" style="font-size: 0.9em;">View Logs</a>
            </div>

            <button type="submit" class="btn btn-primary">Update Task</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
