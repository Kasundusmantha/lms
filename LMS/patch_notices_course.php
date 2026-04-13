<?php
include 'config.php';
try {
    $conn->query("ALTER TABLE notices ADD COLUMN course_id INT;");
    echo "Notices table patched successfully! Added course_id column.";
} catch (Exception $e) {
    echo "Already patched or error: " . $e->getMessage();
}
?>
