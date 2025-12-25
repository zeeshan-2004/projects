<?php
// core/functions.php

require_once __DIR__ . '/../config/db.php';

/**
 * Fetch all subjects from the database.
 * 
 * @param mysqli $conn
 * @return array Associative array of subjects
 */
function get_all_subjects($conn) {
    $sql = "SELECT * FROM subjects";
    $result = mysqli_query($conn, $sql);
    $subjects = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $subjects[] = $row;
        }
    }
    return $subjects;
}

/**
 * Add a new student and their marks.
 * 
 * @param mysqli $conn
 * @param string $name
 * @param string $roll_no
 * @param string $class
 * @param array $marks Array of [subject_id => marks]
 * @return array Success status and messages
 */
function add_student_with_marks($conn, $name, $roll_no, $class, $marks) {
    $errors = [];
    
    // Validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($roll_no)) $errors[] = "Roll number is required.";
    
    // Check duplicate roll no
    $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE roll_no = ?");
    mysqli_stmt_bind_param($stmt, "s", $roll_no);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Roll number already exists.";
    }
    mysqli_stmt_close($stmt);

    // Validate marks
    foreach ($marks as $sub_id => $score) {
        if (!is_numeric($score) || $score < 0 || $score > 100) {
            $errors[] = "Marks must be between 0 and 100.";
            break; 
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Insert Student
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO students (name, roll_no, class) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $roll_no, $class);
        mysqli_stmt_execute($stmt);
        $student_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Insert Marks
        $stmt = mysqli_prepare($conn, "INSERT INTO marks (student_id, subject_id, marks_obtained) VALUES (?, ?, ?)");
        foreach ($marks as $sub_id => $score) {
            mysqli_stmt_bind_param($stmt, "iii", $student_id, $sub_id, $score);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        return ['success' => true, 'message' => "Student added successfully."];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'errors' => ["Database error: " . $e->getMessage()]];
    }
}

/**
 * Fetch raw student data and converting it to a structured report using PHP ARRAYS.
 * 
 * @param mysqli $conn
 * @return array Processed and ranked student data
 */
function get_student_results($conn) {
    // 1. Fetch RAW data only (Restriction: No SQL aggregation)
    $sql = "SELECT s.student_id, s.name, s.roll_no, s.class, sub.subject_name, m.marks_obtained 
            FROM students s 
            JOIN marks m ON s.student_id = m.student_id 
            JOIN subjects sub ON m.subject_id = sub.subject_id";
    
    $result = mysqli_query($conn, $sql);
    $raw_data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $raw_data[] = $row;
        }
    }

    // 2. Process Data into Arrays (Grouping by Student)
    $students = [];
    foreach ($raw_data as $row) {
        $id = $row['student_id'];
        if (!isset($students[$id])) {
            $students[$id] = [
                'id' => $row['student_id'],
                'name' => $row['name'],
                'roll_no' => $row['roll_no'],
                'class' => $row['class'],
                'subjects' => [], // Array to hold subject scores
                'marks_list' => [] // Simple array of scores for calculation
            ];
        }
        $students[$id]['subjects'][$row['subject_name']] = (int)$row['marks_obtained'];
        $students[$id]['marks_list'][] = (int)$row['marks_obtained'];
    }

    // 3. Perform Calculations (Total, Avg, Grade) using Array Functions
    foreach ($students as &$student) {
        $marks = $student['marks_list'];
        
        // Validation: Verify all data is present
        if (count($marks) === 0) {
            $student['total'] = 0;
            $student['percentage'] = 0;
            $student['grade'] = 'F';
            continue;
        }

        // Array Functions for Calculation
        $student['total'] = array_sum($marks);
        $student['count'] = count($marks);
        $student['percentage'] = $student['total'] / $student['count']; // Assuming 100 per subject
        
        $student['grade'] = calculate_grade($student['percentage']);
        $student['is_pass'] = $student['grade'] !== 'F';
    }
    unset($student); // Break reference

    // 4. Ranking (Sorting using PHP usort/array_multisort)
    // Sort by Total Marks Descending
    usort($students, function($a, $b) {
        if ($a['total'] == $b['total']) return 0;
        return ($a['total'] < $b['total']) ? 1 : -1;
    });

    // Assign Ranks (Handling ties)
    $rank = 1;
    $prev_score = -1;
    $actual_rank = 1;
    
    foreach ($students as $key => &$student) {
        if ($prev_score !== $student['total']) {
            $rank = $actual_rank;
        }
        $student['rank'] = $rank;
        $prev_score = $student['total'];
        $actual_rank++;
    }
    unset($student);

    return $students;
}

/**
 * Determine grade based on percentage.
 * 
 * @param float $percentage
 * @return string
 */
function calculate_grade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 50) return 'D';
    return 'F';
}
?>
