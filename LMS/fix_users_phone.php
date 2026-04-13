<?php
include 'config.php';
try {
    $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL, ADD COLUMN parent_phone VARCHAR(20) DEFAULT NULL;");
    echo "Users table patched successfully! Added 'phone' and 'parent_phone' columns.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
