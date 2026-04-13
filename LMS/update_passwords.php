<?php
include 'config.php';

// This script finds all users with unhashed passwords and hashes them securely.
$users = $conn->query("SELECT id, password FROM users");

$updated = 0;
while($row = $users->fetch_assoc()){
    // Bcrypt hashes are exactly 60 characters long.
    if(strlen($row['password']) < 60) { 
        $new_hash = password_hash($row['password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $new_hash, $row['id']);
        $stmt->execute();
        
        echo "Updated password for user ID " . $row['id'] . "<br>";
        $updated++;
    }
}

if ($updated == 0) {
    echo "No passwords needed updating. They might already be active hashes.";
} else {
    echo "<br>Finished updating $updated passwords securely.";
    echo "<br><b>Please delete this file (update_passwords.php) after running it for security reasons!</b>";
}
?>
