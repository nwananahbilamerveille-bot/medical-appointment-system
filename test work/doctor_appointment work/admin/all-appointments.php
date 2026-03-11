<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/functions.php';
requireLogin();

if (getCurrentUserRole() !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$conn = getConnection();

// Get all appointments with filters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$query = "
    SELECT a.*, 
           d.specialization,
           u1.full_name as doctor_name,
           u2.full_name as patient_name,
           u2.email as patient_email
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u1 ON d.user_id = u1.id
    JOIN users u2 ON a.patient_id = u2.id
    WHERE 1=1
";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $query .= " AND a.appointment_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND a.appointment_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'completed') as completed,
        SUM(status = 'cancelled') as cancelled,
        SUM(status = 'scheduled') as scheduled,
        SUM(status = 'confirmed') as confirmed
    FROM appointments
")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Appointments - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/navbar-admin.php'; ?>
    
    <div class="container-fluid mt-4">
        <h2>All Appointments</h2>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6>Total</h6>
                        <h3><?php echo $stats['total']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6>Completed</h6>
                        <h3><?php echo $stats['completed']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6>Scheduled</h6>
                        <h3><?php echo $stats['scheduled']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6>Confirmed</h6>
                        <h3><?php echo $stats['confirmed']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6>Cancelled</h6>
                        <h3><?php echo $stats['cancelled']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <input type="date" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($date_from); ?>"
                               placeholder="From Date">
                    </div>
                    
                    <div class="col-md-3">
                        <input type="date" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($date_to); ?>"
                               placeholder="To Date">
                    </div>
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Appointments Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="appointmentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Booked On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($app = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo str_pad($app['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($app['patient_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo $app['patient_email']; ?></small>
                                    </td>
                                    <td>
                                        Dr. <?php echo htmlspecialchars($app['doctor_name']); ?><br>
                                        <small class="text-muted"><?php echo $app['specialization']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['specialization']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($app['appointment_date'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'scheduled' => 'warning',
                                            'confirmed' => 'info',
                                            'completed' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $class = $status_class[$app['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($app['created_at'])); ?></small><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($app['created_at'])); ?></small>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#appointmentsTable').DataTable({
            "pageLength": 25,
            "order": [[4, 'desc']]
        });
    });
    </script>
</body>
</html>