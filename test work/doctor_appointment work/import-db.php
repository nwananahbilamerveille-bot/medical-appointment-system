<?php
/**
 * Simple Database Importer
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'doctor_booking';

try {
    // Connect without selecting database
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Drop database if exists
    $conn->query("DROP DATABASE IF EXISTS `" . $db_name . "`");
    
    // Read SQL file
    $sql_file = __DIR__ . '/database.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("database.sql not found!");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Execute all statements
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if ($conn->query($statement)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = $conn->error;
            }
        }
    }
    
    $conn->close();
    
    $status = 'success';
    $message = "Database setup complete! Executed $success_count statements.";
    
    if ($error_count > 0) {
        $status = 'warning';
        $message .= " ($error_count errors)";
    }
    
} catch (Exception $e) {
    $status = 'error';
    $message = $e->getMessage();
    $errors = [$message];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                <h4>✅ Success!</h4>
                <p><?php echo $message; ?></p>
                <hr>
                <p><strong>Login with:</strong></p>
                <p><strong>Admin:</strong> admin@hospital.com / admin123<br>
                <strong>Doctor:</strong> dr.diggle@hospital.com / doctor123</p>
                <a href="index.php" class="btn btn-primary">Go Home</a>
                <a href="auth/login.php" class="btn btn-success">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <h4>❌ Error</h4>
                <p><?php echo $message; ?></p>
                <?php if (!empty($errors)): ?>
                    <hr>
                    <strong>Details:</strong>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <hr>
                <p><strong>Troubleshooting:</strong></p>
                <ul>
                    <li>Make sure MySQL/XAMPP is running</li>
                    <li>Check phpMyAdmin to verify MySQL connection</li>
                    <li>Ensure database.sql exists in project root</li>
                    <li>Try importing database.sql manually in phpMyAdmin</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
