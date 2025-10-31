<?php
$password = 'Team$1234';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Username: murali@visualmediatech.com<br>";
echo "Copy this hash into your SQL query: " . $hashed_password;
// Example output: $2y$10$gL... (your hash will be different)
?>