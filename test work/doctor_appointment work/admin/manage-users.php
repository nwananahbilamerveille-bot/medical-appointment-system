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

// Handle user actions
if (isset($_GET['action'])) {
    $user_id = $_GET['id'];
    $action = $_GET['action'];
    
    switch($action) {
        case 'activate':
            $conn->query("UPDATE users SET is_active = 1 WHERE id = $user_id");
            $message = "User activated!";
            break;
        case 'deactivate':
            $conn->query("UPDATE users SET is_active = 0 WHERE id = $user_id");
            $message = "User deactivated!";
            break;
        case 'delete':
            $conn->query("DELETE FROM users WHERE id = $user_id");
            $message = "User deleted!";
            break;
    }
}

// Get all users
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Get user counts
$counts = $conn->query("
    SELECT role, COUNT(*) as count, 
           SUM(is_active = 1) as active,
           SUM(is_active = 0) as inactive
    FROM users 
    GROUP BY role
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/navbar-admin.php'; ?>
    
    <div class="container-fluid mt-4">
        <?php if(isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php foreach($counts as $count): ?>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title text-uppercase"><?php echo $count['role']; ?>s</h5>
                            <h2><?php echo $count['count']; ?></h2>
                            <div class="d-flex justify-content-around">
                                <small class="text-success">✅ <?php echo $count['active']; ?> Active</small>
                                <small class="text-danger">❌ <?php echo $count['inactive']; ?> Inactive</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name or email"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="patient" <?php echo $role_filter === 'patient' ? 'selected' : ''; ?>>Patients</option>
                            <option value="doctor" <?php echo $role_filter === 'doctor' ? 'selected' : ''; ?>>Doctors</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="manage-users.php" class="btn btn-outline-secondary w-100">Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Users (<?php echo $users->num_rows; ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                        <small class="text-muted">📞 <?php echo $user['phone'] ?? 'N/A'; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['role'] === 'admin' ? 'danger' : 
                                                   ($user['role'] === 'doctor' ? 'success' : 'primary');
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                        <?php if($user['role'] === 'doctor' && $user['is_verified']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php elseif($user['role'] === 'doctor'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($user['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if($user['is_active']): ?>
                                                <a href="?action=deactivate&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-warning" title="Deactivate">
                                                   ❌
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=activate&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-success" title="Activate">
                                                   ✅
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Delete this user? This action cannot be undone!')"
                                               title="Delete">
                                               🗑️
                                            </a>
                                            
                                            <button class="btn btn-info" 
                                                    onclick="viewUserDetails(<?php echo $user['id']; ?>)"
                                                    title="View Details">
                                                👁️
                                            </button>
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
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            "pageLength": 25,
            "order": [[5, 'desc']]
        });
    });
    
    function viewUserDetails(userId) {
        // AJAX call to get user details
        fetch(`get-user-details.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                // Create modal with user details
                const modal = `
                    <div class="modal fade" id="userModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">User Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Name:</strong> ${data.full_name}</p>
                                    <p><strong>Email:</strong> ${data.email}</p>
                                    <p><strong>Phone:</strong> ${data.phone || 'N/A'}</p>
                                    <p><strong>Address:</strong> ${data.address || 'N/A'}</p>
                                    <p><strong>Role:</strong> ${data.role}</p>
                                    <p><strong>Status:</strong> ${data.is_active ? 'Active' : 'Inactive'}</p>
                                    <p><strong>Joined:</strong> ${new Date(data.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modal);
                const userModal = new bootstrap.Modal(document.getElementById('userModal'));
                userModal.show();
                
                // Remove modal after hiding
                document.getElementById('userModal').addEventListener('hidden.bs.modal', function () {
                    this.remove();
                });
            });
    }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>