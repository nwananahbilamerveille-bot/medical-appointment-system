<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getConnection();

// Get doctor info
$doctor_stmt = $conn->prepare("
    SELECT d.*, u.email, u.phone 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE u.id = ?
");
$doctor_stmt->bind_param("i", $user_id);
$doctor_stmt->execute();
$doctor = $doctor_stmt->get_result()->fetch_assoc();

// Get today's appointments
$today = date('Y-m-d');
$today_appointments = $conn->prepare("
    SELECT a.*, u.full_name as patient_name, u.phone as patient_phone
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND a.appointment_date = ?
    AND a.status IN ('scheduled', 'confirmed')
    ORDER BY a.appointment_time
");
$doctor_id = $doctor['id'];
$today_appointments->bind_param("is", $doctor_id, $today);
$today_appointments->execute();
$today_apps = $today_appointments->get_result();

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN appointment_date = '$today' THEN 1 ELSE 0 END) as today,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN appointment_date > '$today' AND status IN ('scheduled', 'confirmed') THEN 1 ELSE 0 END) as upcoming
    FROM appointments 
    WHERE doctor_id = $doctor_id
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .doctor-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .stat-card {
            border-radius: 10px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .patient-card {
            border-left: 4px solid #28a745;
            margin-bottom: 15px;
        }
        .quick-action {
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            background: white;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
        }
        .quick-action:hover {
            background: #28a745;
            color: white;
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-user-md"></i> Doctor Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar-alt"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
                <span class="navbar-text ms-3">
                    <i class="fas fa-user-md"></i> Dr. <?php echo $_SESSION['full_name']; ?>
                </span>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="doctor-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Welcome, Dr. <?php echo $_SESSION['full_name']; ?>! 👨‍⚕️</h1>
                    <p class="lead mb-0"><?php echo $doctor['specialization'] ?? 'General Practitioner'; ?></p>
                    <p class="mb-0"><?php echo $doctor['qualification'] ?? ''; ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white text-dark rounded p-3 d-inline-block">
                        <h6 class="mb-1">Today's Schedule</h6>
                        <h3 class="mb-0 text-success"><?php echo $stats['today'] ?? 0; ?> appointments</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total Patients</h6>
                                <h2><?php echo $stats['total'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Today</h6>
                                <h2><?php echo $stats['today'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Upcoming</h6>
                                <h2><?php echo $stats['upcoming'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Completed</h6>
                                <h2><?php echo $stats['completed'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Today's Appointments -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day"></i> Today's Appointments (<?php echo date('F d, Y'); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if($today_apps->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while($app = $today_apps->fetch_assoc()): ?>
                                    <div class="list-group-item patient-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($app['patient_name']); ?></h6>
                                                <p class="mb-1">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('h:i A', strtotime($app['appointment_time'])); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($app['patient_phone']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?php echo $app['status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                                <div class="mt-2">
                                                    <button class="btn btn-sm btn-outline-success me-1" 
                                                            onclick="updateStatus(<?php echo $app['id']; ?>, 'completed')">
                                                        <i class="fas fa-check"></i> Done
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="updateStatus(<?php echo $app['id']; ?>, 'cancelled')">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <h5>No appointments today</h5>
                                <p class="text-muted">Enjoy your day!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Notes -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Quick Notes</h5>
                    </div>
                    <div class="card-body">
                        <form id="quickNotesForm">
                            <div class="mb-3">
                                <textarea class="form-control" rows="3" placeholder="Add notes for today..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save Note
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="quick-action" onclick="window.location.href='appointments.php'">
                                    <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                    <h6>All Appointments</h6>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="quick-action" onclick="window.location.href='profile.php'">
                                    <i class="fas fa-user-edit fa-2x mb-2"></i>
                                    <h6>Edit Profile</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="quick-action" onclick="window.location.href='availability.php'">
                                    <i class="fas fa-calendar-plus fa-2x mb-2"></i>
                                    <h6>Set Availability</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="quick-action" onclick="window.location.href='../auth/logout.php'">
                                    <i class="fas fa-sign-out-alt fa-2x mb-2"></i>
                                    <h6>Logout</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Doctor Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Doctor Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Specialization:</strong><br>
                            <?php echo htmlspecialchars($doctor['specialization'] ?? 'Not set'); ?>
                        </p>
                        <p class="mb-2">
                            <strong>Experience:</strong><br>
                            <?php echo htmlspecialchars($doctor['experience_years'] ?? 0); ?> years
                        </p>
                        <p class="mb-2">
                            <strong>Contact:</strong><br>
                            <?php echo htmlspecialchars($doctor['email'] ?? ''); ?><br>
                            <?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>
                        </p>
                        <p class="mb-0">
                            <strong>Bio:</strong><br>
                            <?php echo substr(htmlspecialchars($doctor['bio'] ?? 'No bio added'), 0, 100); ?>...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <p class="mb-0 text-muted">© 2024 MedAppoint - Doctor Panel</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(appointmentId, status) {
            if(confirm('Update appointment status to ' + status + '?')) {
                const formData = new FormData();
                formData.append('appointment_id', appointmentId);
                formData.append('status', status);
                
                fetch('update-appointment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        // Quick notes form
        document.getElementById('quickNotesForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const note = this.querySelector('textarea').value;
            if(note.trim()) {
                alert('Note saved: ' + note);
                this.querySelector('textarea').value = '';
            }
        });
        
        // Welcome for new doctors
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('new') === '1') {
            alert('🎉 Welcome to MedAppoint Doctor Panel!\nPlease complete your profile and set your availability.');
        }
    </script>
</body>
</html>