<?php
// public/results.php
require_once __DIR__ . '/core/functions.php';

$students = get_student_results($conn);

// Get unique subject names for table header
// Logic: iterate all students to find all possible subjects, or just query db.
// Since we query DB for adding, let's just use get_all_subjects for the header to be consistent.
$subjects_db = get_all_subjects($conn);
$subject_names = array_column($subjects_db, 'subject_name');

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Class Results Analysis</h2>
        <a href="add_student.php"><button>+ Add Student</button></a>
    </div>

    <?php if (empty($students)): ?>
        <p>No records found. <a href="add_student.php">Add a student</a> to get started.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th width="50">Rank</th>
                        <th>Roll No</th>
                        <th>Name</th>
                        <?php foreach ($subject_names as $sub): ?>
                            <th><?php echo htmlspecialchars($sub); ?></th>
                        <?php endforeach; ?>
                        <th>Total</th>
                        <th>Avg / %</th>
                        <th>Grade</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr class="rank-<?php echo $student['rank']; ?>">
                            <td style="font-weight: bold; text-align: center;">#<?php echo $student['rank']; ?></td>
                            <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($student['name']); ?></strong><br>
                                <small style="color:#666;"><?php echo htmlspecialchars($student['class']); ?></small>
                            </td>
                            
                            <!-- Dynamic Subject Columns -->
                            <?php foreach ($subject_names as $sub): ?>
                                <td>
                                    <?php 
                                        echo isset($student['subjects'][$sub]) ? $student['subjects'][$sub] : '-';
                                    ?>
                                </td>
                            <?php endforeach; ?>

                            <td style="font-weight: bold;"><?php echo $student['total']; ?></td>
                            <td><?php echo number_format($student['percentage'], 1); ?>%</td>
                            <td style="font-weight: bold;"><?php echo $student['grade']; ?></td>
                            <td>
                                <?php if($student['is_pass']): ?>
                                    <span class="badge badge-pass">PASS</span>
                                <?php else: ?>
                                    <span class="badge badge-fail">FAIL</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Statistics / Analysis Section using Array Functions -->
<?php if (!empty($students)): 
    // Example of further array analysis
    $totals = array_column($students, 'total');
    $highest_total = max($totals);
    $lowest_total = min($totals);
    $class_avg = array_sum($totals) / count($totals);
    
    // Count Pass/Fail
    $pass_count = 0;
    foreach($students as $s) { if($s['is_pass']) $pass_count++; }
    $fail_count = count($students) - $pass_count;
?>
<div class="card">
    <h3>Class Statistics (Calculated via PHP Arrays)</h3>
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div><strong>Students:</strong> <?php echo count($students); ?></div>
        <div><strong>Class Average:</strong> <?php echo number_format($class_avg, 2); ?></div>
        <div><strong>Highest Score:</strong> <?php echo $highest_total; ?></div>
        <div><strong>Lowest Score:</strong> <?php echo $lowest_total; ?></div>
        <div><strong>Pass:</strong> <?php echo $pass_count; ?></div>
        <div><strong>Fail:</strong> <?php echo $fail_count; ?></div>
    </div>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
