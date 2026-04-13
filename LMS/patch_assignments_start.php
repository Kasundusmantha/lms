<?php
include 'config.php';
try {
    $conn->query("ALTER TABLE assignments ADD COLUMN start_date DATETIME;");
    echo "Assignments table patched successfully! Added start_date column.";
} catch (Exception $e) {
    echo "Already patched or error: " . $e->getMessage();
}
?>
