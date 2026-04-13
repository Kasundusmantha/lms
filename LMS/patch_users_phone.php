<?php
include 'config.php';

try {
    $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER role, ADD COLUMN parent_phone VARCHAR(20) AFTER phone;");
    echo "Users table updated successfully: phone and parent_phone columns added.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
