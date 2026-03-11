<?php
// Configure session before starting
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    // For debugging - remove this after testing
    // error_log("Session check failed - user_id: " . ($_SESSION['user_id'] ?? 'not set') . ", role: " . ($_SESSION['role'] ?? 'not set'));
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getConnection();

// Get upcoming appointments
$upcoming = $conn->prepare("
    SELECT a.*, d.specialization, u.full_name as doctor_name 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.patient_id = ? 
    AND a.appointment_date >= CURDATE()
    AND a.status IN ('scheduled', 'confirmed')
    ORDER BY a.appointment_date, a.appointment_time
    LIMIT 5
");
$upcoming->bind_param("i", $user_id);
$upcoming->execute();
$upcoming_appointments = $upcoming->get_result();

// Get recent appointments
$recent = $conn->prepare("
    SELECT a.*, d.specialization, u.full_name as doctor_name 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.patient_id = ? 
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recent->bind_param("i", $user_id);
$recent->execute();
$recent_appointments = $recent->get_result();

// Count total appointments
$counts = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN appointment_date >= CURDATE() AND status IN ('scheduled', 'confirmed') THEN 1 ELSE 0 END) as upcoming,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM appointments 
    WHERE patient_id = $user_id
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .appointment-card {
            border-left: 4px solid #007bff;
        }
        .completed-card {
            border-left: 4px solid #28a745;
        }
        .cancelled-card {
            border-left: 4px solid #dc3545;
        }
        .quick-action {
            padding: 15px;
            text-align: center;
            border-radius: 10px;
            background: white;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .quick-action:hover {
            background: #007bff;
            color: white;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-stethoscope"></i> MedAppoint
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
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
                <span class="navbar-text ms-3">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['full_name']; ?>
                </span>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Welcome back, <?php echo explode(' ', $_SESSION['full_name'])[0]; ?>! 👋</h1>
                    <p class="lead mb-0">Manage your health appointments in one place</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="appointments.php?action=book" class="btn btn-light btn-lg">
                        <i class="fas fa-plus-circle"></i> Book New Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total Appointments</h6>
                                <h2><?php echo $counts['total'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Upcoming</h6>
                                <h2><?php echo $counts['upcoming'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Completed</h6>
                                <h2><?php echo $counts['completed'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Column: Upcoming Appointments -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day"></i> Upcoming Appointments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if($upcoming_appointments->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while($app = $upcoming_appointments->fetch_assoc()): ?>
                                    <div class="list-group-item appointment-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6>Dr. <?php echo $app['doctor_name']; ?></h6>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-stethoscope"></i> <?php echo $app['specialization']; ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($app['appointment_date'])); ?>
                                                    <i class="fas fa-clock ms-2"></i> <?php echo date('h:i A', strtotime($app['appointment_time'])); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?php echo $app['status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                                <div class="mt-2">
                                                    <?php if($app['status'] === 'scheduled'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="cancelAppointment(<?php echo $app['id']; ?>)">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewDetails(<?php echo $app['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No upcoming appointments</h5>
                                <p class="text-muted">You don't have any scheduled appointments</p>
                                <a href="appointments.php?action=book" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Book Your First Appointment
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Appointments -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if($recent_appointments->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($app = $recent_appointments->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong>Dr. <?php echo $app['doctor_name']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $app['specialization']; ?></small>
                                                </td>
                                                <td><?php echo date('M d', strtotime($app['appointment_date'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = [
                                                        'scheduled' => 'warning',
                                                        'confirmed' => 'info',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger'
                                                    ];
                                                    $class = $statusClass[$app['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $class; ?>">
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary" 
                                                            onclick="viewDetails(<?php echo $app['id']; ?>)">
                                                        Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No appointments yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Quick Actions -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="quick-action" onclick="window.location.href='appointments.php?action=book'">
                                    <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                    <h6>Book Appointment</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="quick-action" onclick="window.location.href='appointments.php'">
                                    <i class="fas fa-list fa-2x mb-2"></i>
                                    <h6>View All</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="quick-action" onclick="window.location.href='../auth/logout.php'">
                                    <i class="fas fa-sign-out-alt fa-2x mb-2"></i>
                                    <h6>Logout</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="quick-action" onclick="window.location.href='profile.php'">
                                    <i class="fas fa-user fa-2x mb-2"></i>
                                    <h6>Profile</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tips -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Health Tips</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-heart"></i> Stay Healthy</h6>
                            <p class="small mb-2">• Drink plenty of water daily</p>
                            <p class="small mb-2">• Get 7-8 hours of sleep</p>
                            <p class="small mb-0">• Exercise for 30 minutes daily</p>
                        </div>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-calendar-check"></i> Appointment Tips</h6>
                            <p class="small mb-2">• Arrive 10 minutes early</p>
                            <p class="small mb-2">• Bring your ID and insurance</p>
                            <p class="small mb-0">• List your symptoms/questions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <p class="mb-0 text-muted">© 2024 MedAppoint - Doctor Appointment System</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelAppointment(id) {
            if(confirm('Are you sure you want to cancel this appointment?')) {
                fetch('../api/cancel-appointment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
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
        
        function viewDetails(id) {
            window.location.href = 'appointments.php?view=' + id;
        }
        
        // Welcome message for new users
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('new') === '1') {
            alert('🎉 Welcome to MedAppoint! Your account has been created successfully.');
        }
    </script>
</body>
</html>