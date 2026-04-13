<?php
include 'config.php';
try {
    $conn->query("CREATE TABLE IF NOT EXISTS notices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        message TEXT,
        created_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Notices table created successfully!";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
