<?php
include 'config.php';
try {
    $conn->query("ALTER TABLE courses ADD COLUMN course_end_time TIME;");
    echo "Courses table patched successfully! Added course_end_time column.";
} catch (Exception $e) {
    echo "Already patched or error: " . $e->getMessage();
}
?>
