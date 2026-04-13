<?php
include 'config.php';
$res = $conn->query("SHOW COLUMNS FROM attendance");
while($row = $res->fetch_assoc()){
    print_r($row);
}
?>
