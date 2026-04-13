<?php
include 'config.php';
try {
    $conn->query("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        course_id INT,
        attendance_date DATE,
        status VARCHAR(50) DEFAULT 'Present',
        UNIQUE KEY student_course_date (student_id, course_id, attendance_date)
    )");
    echo "Attendance table created successfully!";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
