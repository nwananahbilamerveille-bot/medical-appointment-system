<?php
session_start();

echo "<h1>Session Debug Info</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "PHP Session Save Path: " . ini_get('session.save_path') . "\n";
echo "\nSession Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<h2>Stored Session Files:</h2>";
$save_path = ini_get('session.save_path');
if (!empty($save_path) && is_dir($save_path)) {
    echo "Session files in $save_path:<br>";
    $files = array_slice(scandir($save_path), 2);
    echo "<pre>";
    foreach ($files as $file) {
        if (strpos($file, 'sess_') === 0) {
            echo $file . " (Modified: " . date('Y-m-d H:i:s', filemtime($save_path . '/' . $file)) . ")\n";
        }
    }
    echo "</pre>";
} else {
    echo "Session save path not found or not a directory\n";
}

// Try to set a test session variable
$_SESSION['test_var'] = 'test_value_' . time();
echo "<p>Test session variable set. <a href='session-debug.php'>Refresh this page</a> to verify persistence.</p>";

echo "<hr>";
echo "<h2>Test Navigation:</h2>";
echo "<a href='patient/dashboard.php'>Go to Patient Dashboard</a><br>";
echo "<a href='patient/appointments.php'>Go to Patient Appointments</a><br>";
echo "<a href='patient/profile.php'>Go to Patient Profile</a><br>";
?>
