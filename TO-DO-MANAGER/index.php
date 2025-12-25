<?php
/* === index.php ===
 * Main page: Lists all tasks with filters and AJAX actions.
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

requireLogin();
$user_id = currentUserId();

// Fetch options for filters (Assuming categories are global or user specific? 
// For now, let's assume global categories or handle them later. 
// If personal categories required, we'd scope this too. 
// Prompt says "each user only accesses their own data", implies tasks. 
// Categories might be shared metadata. I'll leave categories global for now.)
$cats_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");

// Handle Filters
$search = $_GET['q'] ?? '';
$filter_cat = $_GET['category_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_overdue = isset($_GET['overdue']) ? true : false;

// Build Query - USER SCOPED
$sql = "SELECT tasks.*, categories.name AS category_name 
        FROM tasks 
        LEFT JOIN categories ON tasks.category_id = categories.id 
        WHERE tasks.user_id = ? ";

$types = "i";
$params = [$user_id];

if ($search) {
    $sql .= "AND (tasks.title LIKE ? OR tasks.description LIKE ?) ";
    $param_search = "%$search%";
    $types .= "ss";
    $params[] = $param_search;
    $params[] = $param_search;
}

if ($filter_cat) {
    $sql .= "AND tasks.category_id = ? ";
    $types .= "i";
    $params[] = $filter_cat;
}

if ($filter_status) {
    $sql .= "AND tasks.status = ? ";
    $types .= "s";
    $params[] = $filter_status;
}

if ($filter_priority) {
    $sql .= "AND tasks.priority = ? ";
    $types .= "s";
    $params[] = $filter_priority;
}

if ($filter_overdue) {
    $sql .= "AND (tasks.due_date IS NOT NULL AND tasks.due_date < CURDATE() AND tasks.status != 'completed') ";
}

// Updated Order: Ascending by Created Date
$sql .= "ORDER BY tasks.created_at ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>To Do Manager</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css?v=<?php echo time(); ?>">
    <script>
        function toggleStatus(id, btn) {
            // Add rotation animation class to SVG if desired
            const svg = btn.querySelector('svg');
            if(svg) svg.style.transition = 'transform 0.5s';
            if(svg) svg.style.transform = 'rotate(180deg)';

            fetch('tasks/toggle_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const badge = btn.closest('tr').querySelector('.status-badge');
                    badge.className = 'status-badge badge ' + data.badge_class;
                    badge.textContent = data.label;
                    if(svg) setTimeout(() => { svg.style.transform = 'rotate(0deg)'; }, 500);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error(err));
        }

        function deleteTask(id, btn) {
            if(!confirm('Are you sure? This will delete the task and its attachments.')) return;

            fetch('tasks/delete_task.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    btn.closest('tr').remove();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Delete failed'));
        }

        // AJAX Filtering
        function updateFilters() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();
            
            // Update URL without reload
            history.pushState(null, '', 'index.php?' + params);

            fetch('index.php?' + params)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newBody = doc.getElementById('taskTableBody').innerHTML;
                document.getElementById('taskTableBody').innerHTML = newBody;
            })
            .catch(err => console.error('Error updating filters:', err));
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Attach listeners to all inputs in the filter form
            const inputs = document.querySelectorAll('#filterForm input, #filterForm select');
            inputs.forEach(input => {
                input.addEventListener('change', updateFilters);
                if (input.type === 'text') {
                    input.addEventListener('input', debounce(updateFilters, 500));
                }
            });
            
            // Handle Clear button to reset form
            const clearBtn = document.querySelector('.btn-secondary[href="index.php"]');
            if(clearBtn) {
                clearBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.getElementById('filterForm').reset();
                    // Reset selects manually if needed, or just redirect
                    // Simpler to just trigger update with empty state
                    history.pushState(null, '', 'index.php');
                    fetch('index.php').then(r=>r.text()).then(h=>{
                         const doc = new DOMParser().parseFromString(h, 'text/html');
                         document.getElementById('taskTableBody').innerHTML = doc.getElementById('taskTableBody').innerHTML;
                         // Also reset values visually
                         document.querySelectorAll('#filterForm input, #filterForm select').forEach(i => {
                             if(i.type==='checkbox') i.checked=false;
                             else i.value='';
                         });
                    });
                });
            }
        });

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
    </script>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0;">To Do List</h1>
            <div style="display:flex; align-items:center;">
                <span style="margin-right: 15px;">Hello, <strong><?php echo escape($_SESSION['user_name'] ?? 'User'); ?></strong></span>
                <a href="tasks/create.php" class="btn btn-primary">
                    <span style="margin-right:8px; font-size:1.2em;">+</span> Add New Task
                </a>
                <a href="logs.php" class="btn btn-secondary" style="margin-left: 10px;">Logs</a>
                <a href="auth/logout.php" class="btn btn-sm btn-danger" style="margin-left: 10px;">Logout</a>
            </div>
        </div>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?php echo escape($_SESSION['flash_type']); ?>">
                <?php 
                echo escape($_SESSION['flash_message']); 
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form action="index.php" method="GET" class="filter-row" id="filterForm">
                <div class="filter-item">
                    <input type="text" name="q" placeholder="Search title or details..." value="<?php echo escape($search); ?>">
                </div>
                <!-- Categories -->
                <div class="filter-item">
                     <select name="category_id">
                        <option value="">All Categories</option>
                        <?php while($cat = mysqli_fetch_assoc($cats_result)): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filter_cat == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <!-- Status -->
                <div class="filter-item">
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $filter_status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <!-- Priority -->
                <div class="filter-item">
                    <select name="priority">
                        <option value="">All Priorities</option>
                        <option value="low" <?php echo $filter_priority == 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $filter_priority == 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $filter_priority == 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <!-- Overdue Checkbox -->
                <div class="filter-item" style="flex: 0; min-width: auto; display: flex; align-items: center; justify-content: center; background: #fff; padding: 0 10px; border: 1px solid #ced4da; border-radius: 4px; height: 38px;">
                    <label style="cursor:pointer; margin: 0; display: flex; align-items: center;">
                        <input type="checkbox" name="overdue" value="1" <?php echo $filter_overdue ? 'checked' : ''; ?> style="width: 16px; height: 16px; margin: 0 8px 0 0;"> 
                        <span style="white-space: nowrap;">Overdue Only</span>
                    </label>
                </div>
                <div class="filter-item" style="flex: 0; display: flex; gap: 10px;">
                    <!-- Filter button removed, auto-updates now -->
                    <!-- Keeping Clear button but handled by JS -->
                    <a href="index.php" class="btn btn-secondary btn-sm" style="height: 38px; min-width: 80px; display: flex; align-items: center; justify-content: center; text-decoration: none;">Clear</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Description</th>
                    <th>Start Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody id="taskTableBody">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php 
                    $counter = 0; // Initialize counter for 0-based index 
                    ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <?php 
                        $is_overdue = is_overdue($row['due_date'], $row['status']);
                        $row_class = $is_overdue ? 'row-overdue' : '';
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td data-label="#"><strong><?php echo $counter++; ?></strong></td>
                            <td data-label="Title">
                                <div class="col-title" title="<?php echo escape($row['title']); ?>"><?php echo escape($row['title']); ?></div>
                            </td>
                            <td data-label="Category"><?php echo escape($row['category_name'] ?: 'Uncategorized'); ?></td>
                            <td data-label="Priority">
                                <span class="badge priority-<?php echo escape($row['priority']); ?>">
                                    <?php echo ucfirst($row['priority']); ?>
                                </span>
                            </td>
                            <td data-label="Description">
                                <div class="col-desc" title="<?php echo escape($row['description']); ?>">
                                    <?php echo escape($row['description']); ?>
                                </div>
                            </td>
                            <td data-label="Start Date"><?php echo format_date($row['start_date']); ?></td>
                            <td data-label="Due Date">
                                <?php echo format_date($row['due_date']); ?>
                                <?php if ($is_overdue): ?>
                                    <span class="overdue-badge">LATE</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <?php
                                $status_formatted = str_replace('_', '-', $row['status']); 
                                $status_label = ucwords(str_replace('_', ' ', $row['status']));
                                ?>
                                <span class="status-badge badge status-<?php echo $status_formatted; ?>">
                                    <?php echo $status_label; ?>
                                </span>
                            </td>
                            <td class="actions" data-label="Actions">
                                <!-- Edit Button -->
                                <a href="tasks/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                    Edit
                                </a>
                                
                                <!-- Toggle Status Button (Icon) -->
                                <button type="button" class="btn btn-sm btn-secondary btn-icon" onclick="toggleStatus(<?php echo $row['id']; ?>, this)" title="Change Status">
                                    <!-- Sync/Cycle SVG Icon -->
                                    <svg viewBox="0 0 24 24">
                                        <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                                    </svg>
                                </button>

                                <!-- Delete Button -->
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteTask(<?php echo $row['id']; ?>, this)" title="Delete">
                                    &times;
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 30px; color:#777;">
                            No tasks found. Select 'Add New Task' to begin!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</body>
</html>
