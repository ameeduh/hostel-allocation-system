<?php
// Run this file ONCE to convert existing plain text passwords to hashed
// Access: http://localhost/HostelSystem/migrate_passwords.php

require_once 'config/database.php';

$db = new Database();

// Get all users with plain text passwords
$sql = "SELECT userID, username, password FROM users WHERE password != '' AND (password NOT LIKE '$2y$%')";
$result = $db->query($sql);

if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $hashedPassword = password_hash($row['password'], PASSWORD_DEFAULT);
        
        $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE userID = " . $row['userID'];
        $db->query($updateSql);
        
        echo "Migrated: " . $row['username'] . " - " . $hashedPassword . "<br>";
    }
    echo "Migration completed!";
} else {
    echo "No users found with plain text passwords.";
}
?>