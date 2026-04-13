<?php
include 'config.php';
echo "Running RAW ALTER...<br>";
$conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(255)");
$conn->query("ALTER TABLE users ADD COLUMN parent_phone VARCHAR(255)");
echo "DONE! Re-checking columns...<br>";
$res = $conn->query("DESCRIBE users");
while($row = $res->fetch_assoc()){
    echo $row['Field'] . "<br>";
}
?>
