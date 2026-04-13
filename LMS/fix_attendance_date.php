<?php
include 'config.php';
try {
    $conn->query("ALTER TABLE attendance ADD COLUMN date DATE;");
    $conn->query("UPDATE attendance SET date = DATE(created_at) WHERE date IS NULL;");
    echo "Attendance table patched successfully! Added 'date' column and backfilled old records.";
} catch (Exception $e) {
    echo "Already patched or error: " . $e->getMessage();
}
?>
