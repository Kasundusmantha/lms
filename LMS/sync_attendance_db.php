<?php
include 'config.php';
try {
    // Check if 'date' column exists, if not add it
    $res = $conn->query("SHOW COLUMNS FROM attendance LIKE 'date'");
    if($res->num_rows == 0) {
        $conn->query("ALTER TABLE attendance ADD COLUMN date DATE;");
        echo "Added 'date' column.<br>";
    }
    
    // Backfill 'date' from 'created_at'
    $conn->query("UPDATE attendance SET date = DATE(created_at) WHERE date IS NULL;");
    echo "Backfilled 'date' column.<br>";

    // Ensure 'user_id' is used (standardize)
    // If 'student_id' exists instead, rename it or ensure user_id is the primary one.
    $res = $conn->query("SHOW COLUMNS FROM attendance LIKE 'student_id'");
    if($res->num_rows > 0) {
        $conn->query("ALTER TABLE attendance CHANGE student_id user_id INT;");
        echo "Renamed 'student_id' to 'user_id'.<br>";
    }

    echo "Attendance table structure synchronized successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
