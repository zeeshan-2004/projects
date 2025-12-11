<?php
/* === logs.php ===
 * Displays recent activity logs.
 */
require_once 'db.php';

// Filter by task ID if provided
$task_id = $_GET['task_id'] ?? null;
$limit = 50;

$sql = "SELECT logs.*, tasks.title as task_title 
        FROM activity_logs logs 
        LEFT JOIN tasks ON logs.task_id = tasks.id 
        WHERE 1=1 ";

$params = [];
$types = "";

if ($task_id) {
    $sql .= "AND logs.task_id = ? ";
    $params[] = $task_id;
    $types .= "i";
}

$sql .= "ORDER BY logs.created_at DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Activity Logs</h1>
            <a href="index.php" class="btn btn-secondary">Back to Tasks</a>
        </div>

        <div class="table-responsive">
        <table class="log-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Time</th>
                    <th style="width: 20%;">Task</th>
                    <th style="width: 20%;">Action</th>
                    <th style="width: 40%;">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['created_at']; ?></td>
                            <td>
                                <?php if ($row['task_title']): ?>
                                    <a href="edit.php?id=<?php echo $row['task_id']; ?>"><?php echo escape($row['task_title']); ?></a>
                                <?php else: ?>
                                    <span style="color:#999;">(Deleted Task)</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo escape(ucfirst(str_replace('_', ' ', $row['action']))); ?></strong></td>
                            <td>
                                <?php 
                                // Try to decode if JSON
                                $json = json_decode($row['details'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                                    if (isset($json['old']) && isset($json['new'])) {
                                        echo escape($json['old']) . " &rarr; " . escape($json['new']);
                                    } else {
                                        echo escape($row['details']);
                                    }
                                } else {
                                    echo escape($row['details']);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;">No logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</body>
</html>
