<?php
// This file generates the correct bcrypt hash for password verification

$password = 'doctor123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";

// Test if the hash works
echo "Verification test: " . (password_verify($password, $hash) ? "PASS" : "FAIL") . "\n";
?>
