<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getConnection();

// Handle filters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

$query = "
    SELECT a.*, d.specialization, u.full_name as doctor_name,
           u.email as doctor_email, u.phone as doctor_phone
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.patient_id = ?
";

$params = [$user_id];
$types = "i";

if (!empty($status_filter)) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_filter)) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .appointment-card {
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a class="nav-link active" href="appointments.php"><i class="fas fa-calendar-alt"></i> Appointments</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-calendar-alt"></i> My Appointments</h2>
            <a href="search-doctors.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Find Doctors
            </a>
        </div>
        
        <!-- Filters -->
        <div class="card filter-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="date" class="form-control" 
                               value="<?php echo htmlspecialchars($date_filter); ?>"
                               onchange="this.form.submit()">
                    </div>
                    <div class="col-md-4">
                        <a href="appointments.php" class="btn btn-outline-secondary w-100">
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Appointments List -->
        <?php if($appointments->num_rows > 0): ?>
            <div class="row">
                <?php while($app = $appointments->fetch_assoc()): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card appointment-card h-100"
                             style="border-left-color: <?php 
                                echo $app['status'] === 'completed' ? '#28a745' : 
                                     ($app['status'] === 'cancelled' ? '#dc3545' : '#007bff'); 
                             ?>;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1">Dr. <?php echo htmlspecialchars($app['doctor_name']); ?></h5>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($app['specialization']); ?>
                                        </p>
                                    </div>
                                    <span class="status-badge bg-<?php 
                                        echo $app['status'] === 'completed' ? 'success' : 
                                               ($app['status'] === 'cancelled' ? 'danger' : 
                                               ($app['status'] === 'confirmed' ? 'info' : 'warning'));
                                    ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="mb-1">
                                        <i class="fas fa-calendar"></i> 
                                        <strong>Date:</strong> <?php echo date('F d, Y', strtotime($app['appointment_date'])); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-clock"></i> 
                                        <strong>Time:</strong> <?php echo date('h:i A', strtotime($app['appointment_time'])); ?>
                                    </p>
                                    <?php if($app['symptoms']): ?>
                                        <p class="mb-0">
                                            <i class="fas fa-notes-medical"></i> 
                                            <strong>Symptoms:</strong> <?php echo substr(htmlspecialchars($app['symptoms']), 0, 50); ?>...
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Booked: <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                                    </small>
                                    <div>
                                        <?php if(in_array($app['status'], ['scheduled', 'confirmed'])): ?>
                                            <button class="btn btn-sm btn-outline-danger me-2" 
                                                    onclick="cancelAppointment(<?php echo $app['id']; ?>)">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewDetails(<?php echo $app['id']; ?>)">
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4>No appointments found</h4>
                <p class="text-muted mb-4">You don't have any appointments matching your filters</p>
                <a href="search-doctors.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i> Find Doctors to Book
                </a>
            </div>
        <?php endif; ?>
    </div>
    
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
            // Show appointment details in modal
            alert('Appointment details for ID: ' + id + '\nThis would show detailed view in a modal.');
        }
    </script>
</body>
</html>