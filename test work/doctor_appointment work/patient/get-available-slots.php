<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/functions.php';
requireLogin();

if (getCurrentUserRole() !== 'patient') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$doctor_id = $_GET['doctor_id'] ?? 0;
$date = $_GET['date'] ?? '';

if (!$doctor_id || !$date) {
    echo json_encode(['slots' => []]);
    exit();
}

$conn = getConnection();
$day_of_week = date('l', strtotime($date));

// Get availability for that day
$availability_stmt = $conn->prepare("
    SELECT * FROM availability 
    WHERE doctor_id = ? AND day_of_week = ?
");
$availability_stmt->bind_param("is", $doctor_id, $day_of_week);
$availability_stmt->execute();
$availability = $availability_stmt->get_result()->fetch_assoc();

$slots = [];

if ($availability) {
    // Generate time slots
    $start = strtotime($availability['start_time']);
    $end = strtotime($availability['end_time']);
    $duration = $availability['slot_duration'] * 60;
    
    while ($start < $end) {
        $time_slot = date('H:i', $start);
        
        // Check if slot is booked
        $booked_check = $conn->prepare("
            SELECT id FROM appointments 
            WHERE doctor_id = ? 
            AND appointment_date = ? 
            AND appointment_time = ?
            AND status NOT IN ('cancelled')
        ");
        $booked_check->bind_param("iss", $doctor_id, $date, $time_slot);
        $booked_check->execute();
        
        if ($booked_check->get_result()->num_rows === 0) {
            $slots[] = $time_slot;
        }
        
        $start += $duration;
    }
}

header('Content-Type: application/json');
echo json_encode(['slots' => $slots]);
?>