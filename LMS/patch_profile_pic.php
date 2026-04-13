<?php
// patch_profile_pic.php - Run once to add profile_pic column
session_start();
include 'config.php';

echo "<h3>Profile Picture Column Patch</h3>";

// Add profile_pic column if not exists
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
if($check->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");
    echo "<p style='color:green'>✅ profile_pic column added successfully!</p>";
} else {
    echo "<p style='color:blue'>ℹ️ profile_pic column already exists.</p>";
}

// Add address column if not exists
$check2 = $conn->query("SHOW COLUMNS FROM users LIKE 'address'");
if($check2->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL");
    echo "<p style='color:green'>✅ address column added successfully!</p>";
} else {
    echo "<p style='color:blue'>ℹ️ address column already exists.</p>";
}

// Create uploads/profile directory
if(!is_dir('uploads/profile')) {
    mkdir('uploads/profile', 0777, true);
    echo "<p style='color:green'>✅ uploads/profile/ directory created.</p>";
} else {
    echo "<p style='color:blue'>ℹ️ uploads/profile/ already exists.</p>";
}

echo "<br><a href='profile.php' style='background:#5E5CE6;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;'>Go to Profile →</a>";
?>
