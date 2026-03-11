<?php
require_once 'config/database.php';

$email = 'nabilla@gmail.com';
$password = 'nabilla123';
$full_name = 'system admin';

// Generate bcrypt hash
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$conn = getConnection();

// Update or insert admin
$stmt = $conn->prepare("
    INSERT INTO users (role, email, password, full_name, is_verified, is_active) 
    VALUES ('admin', ?, ?, ?, 1, 1)
    ON DUPLICATE KEY UPDATE 
    password = ?, full_name = ?, is_verified = 1, is_active = 1
");

$stmt->bind_param("sssss", $email, $hashed_password, $full_name, $hashed_password, $full_name);

if ($stmt->execute()) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px;'>";
    echo "<h3>✓ Admin credentials updated successfully!</h3>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Full Name:</strong> $full_name</p>";
    echo "<p><strong>Hashed:</strong> " . substr($hashed_password, 0, 30) . "...</p>";
    echo "<p style='margin-top: 20px;'><a href='auth/login.php' style='color: #155724; font-weight: bold;'>Go to Login →</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>";
    echo "<h3>✗ Error updating admin credentials</h3>";
    echo "<p>" . $conn->error . "</p>";
    echo "</div>";
}

$conn->close();
?>
