<?php
include 'config.php';
try {
    $conn->query("ALTER TABLE courses ADD COLUMN quiz_time INT DEFAULT 10;");
    echo "Courses table patched successfully! Added quiz_time.";
} catch (Exception $e) {
    echo "Already patched or error: " . $e->getMessage();
}
?>
