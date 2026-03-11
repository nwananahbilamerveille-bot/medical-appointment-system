<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$appointment_id = $data['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit();
}

$conn = getConnection();

// Verify appointment belongs to user
if ($user_role === 'patient') {
    $check = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND patient_id = ?");
    $check->bind_param("ii", $appointment_id, $user_id);
} elseif ($user_role === 'doctor') {
    // Get doctor id
    $doctor_check = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $doctor_check->bind_param("i", $user_id);
    $doctor_check->execute();
    $doctor_id = $doctor_check->get_result()->fetch_assoc()['id'];
    
    $check = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND doctor_id = ?");
    $check->bind_param("ii", $appointment_id, $doctor_id);
} else {
    $check = $conn->prepare("SELECT id FROM appointments WHERE id = ?");
    $check->bind_param("i", $appointment_id);
}

$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or access denied']);
    exit();
}

// Update status to cancelled
$stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $appointment_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error cancelling appointment']);
}
?>