<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/functions.php';
requireLogin();

if (getCurrentUserRole() !== 'doctor') {
    header("Location: ../index.php");
    exit();
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get doctor ID
$doctor_stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$doctor_stmt->bind_param("i", $user_id);
$doctor_stmt->execute();
$doctor_id = $doctor_stmt->get_result()->fetch_assoc()['id'];

// Handle availability update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $slot_duration = $_POST['slot_duration'];
    
    // Check if exists
    $check = $conn->prepare("SELECT id FROM availability WHERE doctor_id = ? AND day_of_week = ?");
    $check->bind_param("is", $doctor_id, $day);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        // Update
        $stmt = $conn->prepare("
            UPDATE availability 
            SET start_time = ?, end_time = ?, slot_duration = ?
            WHERE doctor_id = ? AND day_of_week = ?
        ");
        $stmt->bind_param("ssiis", $start_time, $end_time, $slot_duration, $doctor_id, $day);
    } else {
        // Insert
        $stmt = $conn->prepare("
            INSERT INTO availability (doctor_id, day_of_week, start_time, end_time, slot_duration)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssi", $doctor_id, $day, $start_time, $end_time, $slot_duration);
    }
    
    $stmt->execute();
    $success = "Availability updated successfully!";
}

// Get current availability
$availability_stmt = $conn->prepare("SELECT * FROM availability WHERE doctor_id = ?");
$availability_stmt->bind_param("i", $doctor_id);
$availability_stmt->execute();
$availability = $availability_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$availability_map = [];
foreach ($availability as $slot) {
    $availability_map[$slot['day_of_week']] = $slot;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="#">Doctor Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="appointments.php">Appointments</a>
                <a class="nav-link active" href="availability.php">Availability</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2>Manage Your Availability</h2>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <p class="text-muted">Set your weekly availability. Patients can only book appointments during these hours.</p>
                
                <?php 
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach($days as $day): 
                    $slot = $availability_map[$day] ?? null;
                ?>
                    <div class="day-slot mb-4 p-3 border rounded">
                        <h5><?php echo $day; ?></h5>
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="day" value="<?php echo $day; ?>">
                            
                            <div class="col-md-3">
                                <label>Start Time</label>
                                <input type="time" name="start_time" class="form-control" 
                                       value="<?php echo $slot['start_time'] ?? '09:00'; ?>" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label>End Time</label>
                                <input type="time" name="end_time" class="form-control" 
                                       value="<?php echo $slot['end_time'] ?? '17:00'; ?>" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label>Slot Duration (minutes)</label>
                                <select name="slot_duration" class="form-select">
                                    <option value="15" <?php echo ($slot['slot_duration'] ?? 30) == 15 ? 'selected' : ''; ?>>15 min</option>
                                    <option value="30" <?php echo ($slot['slot_duration'] ?? 30) == 30 ? 'selected' : ''; ?>>30 min</option>
                                    <option value="45" <?php echo ($slot['slot_duration'] ?? 30) == 45 ? 'selected' : ''; ?>>45 min</option>
                                    <option value="60" <?php echo ($slot['slot_duration'] ?? 30) == 60 ? 'selected' : ''; ?>>60 min</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <?php echo $slot ? 'Update' : 'Set'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>