<?php
/**
 * Password Reset Script
 * Sets correct passwords for admin and doctor accounts
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'doctor_booking';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Generate password hashes
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $doctor_password = password_hash('doctor123', PASSWORD_DEFAULT);
    
    // Update admin password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $admin_password, $admin_email);
    $admin_email = 'admin@hospital.com';
    $stmt->execute();
    $admin_updated = $stmt->affected_rows > 0;
    
    // Update all doctor passwords
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE role = 'doctor'");
    $stmt->bind_param("s", $doctor_password);
    $stmt->execute();
    $doctors_updated = $stmt->affected_rows;
    
    $conn->close();
    
    $success = true;
    $message = "Passwords updated successfully!<br>";
    $message .= "Admin: " . ($admin_updated ? "✅ Updated" : "❌ Not found") . "<br>";
    $message .= "Doctors: " . ($doctors_updated > 0 ? "✅ Updated ($doctors_updated records)" : "❌ Not found");
    
} catch (Exception $e) {
    $success = false;
    $message = "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <h4>✅ Success!</h4>
                <p><?php echo $message; ?></p>
                <hr>
                <p><strong>Now you can login with:</strong></p>
                <div class="card mb-3">
                    <div class="card-body">
                        <strong>👨‍⚕️ Admin Account</strong><br>
                        Email: <code>admin@hospital.com</code><br>
                        Password: <code>admin123</code>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <strong>👨‍⚕️ Doctor Accounts</strong><br>
                        Email: <code>dr.diggle@hospital.com</code> (or any doctor email)<br>
                        Password: <code>doctor123</code>
                    </div>
                </div>
                <a href="auth/login.php" class="btn btn-primary btn-lg w-100">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <h4>❌ Error</h4>
                <p><?php echo $message; ?></p>
                <p><strong>Make sure:</strong></p>
                <ul>
                    <li>MySQL is running (check XAMPP)</li>
                    <li>Database doctor_booking exists</li>
                    <li>Users table has data</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
