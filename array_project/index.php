<?php
// public/index.php
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="text-align: center; padding: 50px;">
    <h2>Welcome to Student Result Analyzer</h2>
    <p>A pure PHP & Array-based solution for academic result processing.</p>
    
    <div style="margin-top: 30px;">
        <a href="add_student.php">
            <button style="margin-right: 20px;">Add New Student</button>
        </a>
        <a href="results.php">
            <button style="background: #28a745;">View Results & Rankings</button>
        </a>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
