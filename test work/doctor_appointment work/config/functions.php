<?php
require_once 'database.php';

// Sanitize input
function sanitize($input) {
    $conn = getConnection();
    return htmlspecialchars(strip_tags(trim($input)));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Get user data
function getUserData($user_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Check if user is doctor
function isDoctor($user_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT d.* FROM doctors d 
                          JOIN users u ON d.user_id = u.id 
                          WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get all specializations
function getSpecializations() {
    $conn = getConnection();
    $result = $conn->query("SELECT * FROM specializations ORDER BY name");
    $specializations = [];
    while($row = $result->fetch_assoc()) {
        $specializations[] = $row;
    }
    return $specializations;
}
?>