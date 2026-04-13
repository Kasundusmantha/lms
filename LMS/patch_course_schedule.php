<?php
include 'config.php';
try {
    $conn->query("ALTER TABLE courses ADD COLUMN course_days TEXT, ADD COLUMN course_time TIME;");
    echo "Courses table patched successfully! Added course_days and course_time columns.";
} catch (Exception $e) {
    echo "Already patched or error: " . $e->getMessage();
}
?>
