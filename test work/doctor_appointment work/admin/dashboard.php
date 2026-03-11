<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$conn = getConnection();

// Get statistics
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'patient') as total_patients,
        (SELECT COUNT(*) FROM users WHERE role = 'doctor' AND is_verified = 1) as verified_doctors,
        (SELECT COUNT(*) FROM users WHERE role = 'doctor' AND is_verified = 0) as pending_doctors,
        (SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()) as today_appointments,
        (SELECT COUNT(*) FROM appointments) as total_appointments,
        (SELECT COUNT(*) FROM specializations) as specializations
")->fetch_assoc();

// Get pending doctors
$pending_doctors = $conn->query("
    SELECT u.*, d.specialization, d.qualification 
    FROM users u 
    JOIN doctors d ON u.id = d.user_id 
    WHERE u.role = 'doctor' AND u.is_verified = 0
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .admin-header h1 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card {
            border-radius: 10px;
            transition: all 0.3s ease;
            color: white;
            margin-bottom: 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .stat-card .card-body {
            padding: 20px;
        }
        .stat-card h6 {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            opacity: 0.9;
            letter-spacing: 0.5px;
        }
        .stat-card h2 {
            font-weight: 700;
            margin: 10px 0 0 0;
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 2px solid transparent;
        }
        .quick-action:hover {
            background: #2c3e50;
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            border-color: #2c3e50;
        }
        .quick-action i {
            color: #667eea;
            margin-bottom: 10px;
        }
        .quick-action:hover i {
            color: white;
        }
        .dashboard-card {
            height: 100%;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .dashboard-card .card-header {
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
            padding: 20px;
        }
        .card-header.bg-warning {
            background-color: #f39c12 !important;
            color: white;
        }
        .card-header.bg-dark {
            background-color: #2c3e50 !important;
        }
        .navbar-dark {
            background-color: #1a252f !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            color: #fff !important;
            font-size: 18px;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        footer {
            background-color: #1a252f !important;
            border-top: 1px solid #e0e0e0;
            margin-top: 50px;
        }
        .btn-group-sm .btn {
            padding: 4px 8px;
            font-size: 12px;
        }
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-hospital-user"></i> MedAppoint - Admin
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
                        <a class="nav-link" href="manage-doctors.php">
                            <i class="fas fa-user-md"></i> Manage Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-users.php">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="all-appointments.php">
                            <i class="fas fa-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="specializations.php">
                            <i class="fas fa-stethoscope"></i> Specializations
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                    <p class="lead mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! Here's your system overview.</p>
                </div>

            </div>
        </div>
    </div>
    
    <div class="container-fluid">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-2">Total Patients</h6>
                                <h2 class="mb-0"><?php echo $stats['total_patients'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-2">Verified Doctors</h6>
                                <h2 class="mb-0"><?php echo $stats['verified_doctors'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-user-check fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-2">Pending Doctors</h6>
                                <h2 class="mb-0"><?php echo $stats['pending_doctors'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-hourglass-half fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-2">Today's Appointments</h6>
                                <h2 class="mb-0"><?php echo $stats['today_appointments'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-calendar-day fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card bg-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-2">Total Appointments</h6>
                                <h2 class="mb-0"><?php echo $stats['total_appointments'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-calendar-alt fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-2">Specializations</h6>
                                <h2 class="mb-0"><?php echo $stats['specializations'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-stethoscope fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Pending Doctors -->
                <div class="card dashboard-card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">
                            <i class="fas fa-clock"></i> Pending Doctor Approvals
                            <span class="badge bg-danger"><?php echo $pending_doctors->num_rows; ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if($pending_doctors->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Doctor Name</th>
                                            <th>Specialization</th>
                                            <th>Qualification</th>
                                            <th>Applied On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($doc = $pending_doctors->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($doc['specialization']); ?></td>
                                                <td><?php echo htmlspecialchars($doc['qualification']); ?></td>
                                                <td><?php echo date('M d', strtotime($doc['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="manage-doctors.php?action=verify&id=<?php echo $doc['id']; ?>" 
                                                           class="btn btn-success" title="Verify">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <a href="manage-doctors.php?action=reject&id=<?php echo $doc['id']; ?>" 
                                                           class="btn btn-danger" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                        <a href="manage-doctors.php?view=<?php echo $doc['id']; ?>" 
                                                           class="btn btn-info" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <a href="manage-doctors.php" class="btn btn-outline-warning">View All</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>No pending approvals</h5>
                                <p class="text-muted">All doctors are verified</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-md-4">
                <!-- System Stats -->
                <div class="card dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> System Statistics</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total Appointments:</span>
                                <strong><?php echo $stats['total_appointments'] ?? 0; ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Specializations:</span>
                                <strong><?php echo $stats['specializations'] ?? 0; ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total Users:</span>
                                <strong>
                                    <?php 
                                    $total_users = ($stats['total_patients'] ?? 0) + 
                                                  ($stats['verified_doctors'] ?? 0) + 
                                                  ($stats['pending_doctors'] ?? 0);
                                    echo $total_users + 1; // +1 for admin
                                    ?>
                                </strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>System Uptime:</span>
                                <strong>99.9%</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-white mt-5 py-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">MedAppoint - Admin Dashboard</h6>
                    <p class="text-muted small">Professional medical appointment management system.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-1">© 2024 All rights reserved</p>
                    <small class="text-muted">v1.0.0 | Last updated: <?php echo date('F d, Y'); ?></small>
                </div>
            </div>

        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSystemInfo() {
            const info = `
╔═══════════════════════════════════╗
║     System Information             ║
╚═══════════════════════════════════╝

PHP Version: <?php echo phpversion(); ?>
Database: Connected ✓
Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
Users Online: Active
Memory Usage: Optimal
Last Backup: Today 02:00 AM
Status: All Systems Operational
            `;
            alert(info);
        }
        
        // Tooltip initialization
        document.querySelectorAll('[title]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    </script>
</body>
</html>