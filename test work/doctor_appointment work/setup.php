<?php
/**
 * Database Setup Script
 * This file creates/reimports the database and tables from database.sql
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'doctor_booking';

// Create connection without database selection
$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$executed = 0;

// Drop existing database if it exists
$drop_result = $conn->query("DROP DATABASE IF EXISTS " . $db_name);
if (!$drop_result) {
    $errors[] = "Warning: Could not drop existing database";
}

// Read the SQL file
$sql_file = __DIR__ . '/database.sql';

if (!file_exists($sql_file)) {
    die("Error: database.sql file not found at " . $sql_file);
}

$sql = file_get_contents($sql_file);

// Split by semicolons to execute multiple statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if (!$conn->query($statement)) {
            $errors[] = "Error: " . $conn->error . "<br><small>" . substr($statement, 0, 80) . "...</small>";
        } else {
            $executed++;
        }
    }
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Doctor Appointment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 650px;
            padding: 40px;
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .success-icon {
            font-size: 80px;
            text-align: center;
            margin-bottom: 20px;
        }
        .error-icon {
            font-size: 80px;
            text-align: center;
            margin-bottom: 20px;
        }
        .credential-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .credential-box strong {
            color: #667eea;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .error-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .error-item {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 8px 0;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <?php if (empty($errors)): ?>
            <div class="success-icon">✅</div>
            <h2 class="text-center text-success mb-3">Database Setup Complete!</h2>
            <div class="alert alert-success">
                <p><strong>Successfully executed <?php echo $executed; ?> SQL statements.</strong></p>
                <p>The database "<?php echo $db_name; ?>" has been created with all tables and sample data.</p>
            </div>
            
            <div class="alert alert-info mt-4">
                <h5 class="mb-3">📋 Demo Credentials:</h5>
                
                <div class="credential-box">
                    <strong>👨‍⚕️ Admin Account</strong><br>
                    Email: <code>admin@hospital.com</code><br>
                    Password: <code>admin123</code>
                </div>
                
                <div class="credential-box">
                    <strong>👤 Sample Patient</strong><br>
                    Email: <code>patient@demo.com</code><br>
                    Password: <code>demo123</code><br>
                    <small class="text-muted">Create your own patient account via registration</small>
                </div>
                
                <div class="credential-box">
                    <strong>👨‍⚕️ Available Doctors (6 available)</strong><br>
                    All verified and ready to book:
                    <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                        <li><strong>John Diggle</strong> - Cardiology (250,000 XAF/consultation)</li>
                        <li><strong>Dr Joseph Aristotde</strong> - Dermatology (200,000 XAF/consultation)</li>
                        <li><strong>Dr Nahbilla Nwanah</strong> - Neurology (275,000 XAF/consultation)</li>
                        <li><strong>Dr Yann Brel</strong> - Pediatrics (175,000 XAF/consultation)</li>
                        <li><strong>Dr Thierry Divine</strong> - Orthopedics (300,000 XAF/consultation)</li>
                        <li><strong>Dr Miriam Andra</strong> - General Practice (150,000 XAF/consultation)</li>
                    </ul>
                    Password: <code>doctor123</code>
                </div>
            </div>
            
            <div class="alert alert-warning mt-4">
                <h6>⚠️ Important:</h6>
                <p class="mb-0">Delete this <code>setup.php</code> file from your project after setup is complete for security reasons.</p>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-home"></i> Go to Home Page
                </a>
                <a href="auth/login.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
            
        <?php else: ?>
            <div class="error-icon">❌</div>
            <h2 class="text-center text-danger mb-3">Setup Encountered Issues</h2>
            <div class="alert alert-danger">
                <p><strong><?php echo count($errors); ?> issue(s) encountered:</strong></p>
                <div class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <div class="error-item"><?php echo $error; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <h6>ℹ️ Troubleshooting:</h6>
                <ul class="mb-0">
                    <li>Ensure MySQL server is running (check XAMPP)</li>
                    <li>Verify MySQL credentials in setup.php: root / (empty password)</li>
                    <li>Check that database.sql file exists in the project root</li>
                    <li>Try refreshing this page to retry the setup</li>
                    <li>Or import database.sql manually via phpMyAdmin at <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>
                </ul>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <a href="setup.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-redo"></i> Retry Setup
                </a>
                <a href="http://localhost/phpmyadmin" class="btn btn-outline-secondary btn-lg" target="_blank">
                    <i class="fas fa-database"></i> Open phpMyAdmin
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
