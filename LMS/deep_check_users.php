<?php
include 'config.php';
echo "Checking DB: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br>";
$res = $conn->query("DESCRIBE users");
while($row = $res->fetch_assoc()){
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}
?>
