<?php
mysqli_report(MYSQLI_REPORT_ALL);
include 'config.php';
echo "<h1>BULLDOZER DATABASE SYNC</h1>";

try {
    echo "Attempting to add parent_phone...<br>";
    $conn->query("ALTER TABLE users ADD COLUMN parent_phone VARCHAR(255) DEFAULT NULL");
    echo "SUCCESS!<br>";
} catch (Exception $e) {
    echo "Notice: " . $e->getMessage() . "<br>";
}

try {
    echo "Attempting to add phone if missing...<br>";
    $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(255) DEFAULT NULL");
    echo "SUCCESS!<br>";
} catch (Exception $e) {
    echo "Notice: " . $e->getMessage() . "<br>";
}

echo "<h3>Final Column List for 'users':</h3>";
$res = $conn->query("DESCRIBE users");
while($row = $res->fetch_assoc()){
    echo " - " . $row['Field'] . " (" . $row['Type'] . ")<br>";
}

echo "<h3>Final Column List for 'attendance':</h3>";
$res = $conn->query("DESCRIBE attendance");
while($row = $res->fetch_assoc()){
    echo " - " . $row['Field'] . " (" . $row['Type'] . ")<br>";
}
?>
