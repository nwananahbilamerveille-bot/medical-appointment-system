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

// Handle actions
if (isset($_GET['action'])) {
    $user_id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'verify') {
        $conn->query("UPDATE users SET is_verified = 1 WHERE id = $user_id");
        $message = "Doctor verified successfully!";
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM users WHERE id = $user_id");
        $message = "User deleted successfully!";
    } elseif ($action === 'activate') {
        $conn->query("UPDATE users SET is_active = 1 WHERE id = $user_id");
        $message = "User activated!";
    } elseif ($action === 'deactivate') {
        $conn->query("UPDATE users SET is_active = 0 WHERE id = $user_id");
        $message = "User deactivated!";
    }
}

// Get pending doctors
$pending_doctors = $conn->query("
    SELECT u.*, d.specialization, d.qualification 
    FROM users u 
    JOIN doctors d ON u.id = d.user_id 
    WHERE u.role = 'doctor' AND u.is_verified = 0
");

// Get all doctors
$all_doctors = $conn->query("
    SELECT u.*, d.specialization 
    FROM users u 
    JOIN doctors d ON u.id = d.user_id 
    WHERE u.role = 'doctor'
    ORDER BY u.is_verified, u.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Doctors - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="manage-doctors.php">Doctors</a>
                <a class="nav-link" href="specializations.php">Specializations</a>
                <a class="nav-link" href="manage-users.php">Users</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <?php if(isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Pending Approvals -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">
                            <i class="fas fa-clock"></i> Pending Doctor Approvals
                            <span class="badge bg-danger"><?php echo $pending_doctors->num_rows; ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if($pending_doctors->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while($doctor = $pending_doctors->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6><?php echo $doctor['full_name']; ?></h6>
                                                <small class="text-muted">
                                                    <?php echo $doctor['specialization']; ?><br>
                                                    <?php echo $doctor['email']; ?>
                                                </small>
                                            </div>
                                            <div>
                                                <a href="?action=verify&id=<?php echo $doctor['id']; ?>" 
                                                   class="btn btn-success btn-sm">
                                                   <i class="fas fa-check"></i> Verify
                                                </a>
                                                <a href="?action=delete&id=<?php echo $doctor['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Delete this doctor?')">
                                                   <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No pending approvals.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- All Doctors -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h5 class="mb-0"><i class="fas fa-user-md"></i> All Doctors</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Specialization</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($doctor = $all_doctors->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $doctor['full_name']; ?></td>
                                            <td><?php echo $doctor['specialization']; ?></td>
                                            <td>
                                                <?php if($doctor['is_verified']): ?>
                                                    <span class="badge bg-success">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                                
                                                <?php if($doctor['is_active']): ?>
                                                    <span class="badge bg-info">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if(!$doctor['is_verified']): ?>
                                                        <a href="?action=verify&id=<?php echo $doctor['id']; ?>" 
                                                           class="btn btn-success">
                                                           <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($doctor['is_active']): ?>
                                                        <a href="?action=deactivate&id=<?php echo $doctor['id']; ?>" 
                                                           class="btn btn-warning">
                                                           <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activate&id=<?php echo $doctor['id']; ?>" 
                                                           class="btn btn-info">
                                                           <i class="fas fa-play"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="?action=delete&id=<?php echo $doctor['id']; ?>" 
                                                       class="btn btn-danger"
                                                       onclick="return confirm('Delete this doctor?')">
                                                       <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>