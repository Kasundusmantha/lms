<?php
include 'config.php';

echo "Running Database Master Patch...<br>";

try {
    // 1. Add XP Gamification Column
    $conn->query("ALTER TABLE users ADD COLUMN xp INT DEFAULT 0;");
    echo "Added 'xp' column to users table.<br>";
} catch (Exception $e) {
    echo "Notice: 'xp' column already exists or error: " . $e->getMessage() . "<br>";
}

// 2. Add Missing Tables
$queries = [
    "CREATE TABLE IF NOT EXISTS assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT,
        title VARCHAR(255),
        description TEXT,
        due_date DATE
    )",
    "CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id INT,
        student_id INT,
        file_path VARCHAR(255),
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        quiz_id INT,
        score INT,
        total INT,
        date_taken DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT,
        title VARCHAR(255),
        file_path VARCHAR(255)
    )"
];

foreach($queries as $query) {
    try {
        $conn->query($query);
    } catch (Exception $e) {
        echo "Table Creation Error: " . $e->getMessage() . "<br>";
    }
}
echo "All missing tables verified/created!<br>";
?>
