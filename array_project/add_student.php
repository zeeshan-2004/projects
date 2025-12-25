<?php
// public/add_student.php
require_once __DIR__ . '/core/functions.php';

$subjects = get_all_subjects($conn);
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $roll_no = $_POST['roll_no'] ?? '';
    $class = $_POST['class'] ?? '';
    $marks_input = $_POST['marks'] ?? []; // Array [subject_id => score]

    $result = add_student_with_marks($conn, $name, $roll_no, $class, $marks_input);

    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
    } else {
        $message = implode('<br>', $result['errors']);
        $messageType = 'danger';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Add Student Result</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Student Name:</label>
            <input type="text" name="name" required placeholder="Enter student name">
        </div>

        <div class="form-group">
            <label>Roll Number:</label>
            <input type="text" name="roll_no" required placeholder="Unique Roll No">
        </div>

        <div class="form-group">
            <label>Class/Grade:</label>
            <input type="text" name="class" required placeholder="e.g. 10thA">
        </div>

        <h3>Enter Marks</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
            <?php foreach ($subjects as $subject): ?>
                <div class="form-group">
                    <label><?php echo htmlspecialchars($subject['subject_name']); ?>:</label>
                    <input type="number" name="marks[<?php echo $subject['subject_id']; ?>]" min="0" max="100" required placeholder="0-100">
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit">Save Result</button>
            <a href="index.php" style="margin-left: 20px; color: #666; text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
