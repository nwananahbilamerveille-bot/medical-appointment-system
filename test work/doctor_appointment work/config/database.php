<?php
// Database configuration
// Note: session_start() should be called in each main PHP file before including this file
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'doctor_booking');

// Create connection
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Redirect if not logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Redirect based on role
function redirectBasedOnRole($role) {
    switch($role) {
        case 'patient':
            header("Location: ../patient/dashboard.php");
            break;
        case 'doctor':
            header("Location: ../doctor/dashboard.php");
            break;
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit();
}
?>