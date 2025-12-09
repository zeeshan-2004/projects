<?php
/* === create.php ===
 * Form to create a new task.
 */
session_start();
require_once 'db.php';

// Fetch categories
$cats_sql = "SELECT * FROM categories ORDER BY name ASC";
$cats_result = mysqli_query($conn, $cats_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Task</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Add New Task</h1>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?php echo escape($_SESSION['flash_type']); ?>">
                <?php 
                echo escape($_SESSION['flash_message']); 
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                ?>
            </div>
        <?php endif; ?>

        <form action="store.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title <span style="color:red">*</span></label>
                <input type="text" name="title" id="title" required placeholder="Enter task title">
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <select name="category_id" id="category">
                    <option value="">-- Select Category --</option>
                    <?php while($cat = mysqli_fetch_assoc($cats_result)): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo escape($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label for="priority">Priority</label>
                    <select name="priority" id="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date">
                </div>
                <div style="flex: 1;">
                    <label for="due_date">Due Date</label>
                    <input type="date" name="due_date" id="due_date">
                </div>
            </div>

            <div class="form-group">
                <label for="attachments">Attachments (Max 5MB each; jpg, png, pdf, docx)</label>
                <input type="file" name="attachments[]" id="attachments" multiple accept=".jpg,.jpeg,.png,.pdf,.docx">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="5" placeholder="Enter task details (optional)"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Save Task</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
