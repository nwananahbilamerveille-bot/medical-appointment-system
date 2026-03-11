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
    SELECT d.*, u.email, u.full_name, u.phone, u.address, u.created_at
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE u.id = ?
");
$doctor_stmt->bind_param("i", $user_id);
$doctor_stmt->execute();
$doctor = $doctor_stmt->get_result()->fetch_assoc();

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $qualification = $_POST['qualification'];
    $specialization = $_POST['specialization'];
    $experience = $_POST['experience'];
    $bio = $_POST['bio'];
    $consultation_fee = $_POST['consultation_fee'];
    
    // Update user table
    $user_stmt = $conn->prepare("
        UPDATE users 
        SET full_name = ?, phone = ?, address = ?
        WHERE id = ?
    ");
    $user_stmt->bind_param("sssi", $full_name, $phone, $address, $user_id);
    
    // Update doctors table
    $doctor_update = $conn->prepare("
        UPDATE doctors 
        SET qualification = ?, specialization = ?, experience_years = ?, 
            bio = ?, consultation_fee = ?
        WHERE user_id = ?
    ");
    $doctor_update->bind_param("ssisdi", $qualification, $specialization, $experience, $bio, $consultation_fee, $user_id);
    
    if ($user_stmt->execute() && $doctor_update->execute()) {
        $_SESSION['full_name'] = $full_name;
        $message = "Profile updated successfully!";
        // Refresh doctor data
        $doctor_stmt->execute();
        $doctor = $doctor_stmt->get_result()->fetch_assoc();
    } else {
        $error = "Error updating profile. Please try again.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password
    $pass_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $pass_stmt->bind_param("i", $user_id);
    $pass_stmt->execute();
    $current_hash = $pass_stmt->get_result()->fetch_assoc()['password'];
    
    if (password_verify($current_password, $current_hash)) {
        if ($new_password === $confirm_password) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_pass->bind_param("si", $new_hash, $user_id);
            
            if ($update_pass->execute()) {
                $message = "Password changed successfully!";
            } else {
                $error = "Error changing password.";
            }
        } else {
            $error = "New passwords don't match!";
        }
    } else {
        $error = "Current password is incorrect!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 60px;
            color: #28a745;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .form-card {
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-card i {
            font-size: 30px;
            color: #28a745;
            margin-bottom: 10px;
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a class="nav-link active" href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div class="profile-avatar">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3>Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                    <p class="mb-0"><?php echo htmlspecialchars($doctor['specialization'] ?? 'General Practitioner'); ?></p>
                </div>
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <i class="fas fa-graduation-cap"></i>
                                <h5><?php echo htmlspecialchars($doctor['qualification'] ?? 'Not set'); ?></h5>
                                <p class="text-muted mb-0">Qualification</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <i class="fas fa-clock"></i>
                                <h5><?php echo htmlspecialchars($doctor['experience_years'] ?? 0); ?> years</h5>
                                <p class="text-muted mb-0">Experience</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Personal Information -->
            <div class="col-md-6">
                <div class="card form-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-user-edit"></i> Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name" 
                                           value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($doctor['email']); ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone *</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Consultation Fee (XAF)</label>
                                    <input type="number" class="form-control" name="consultation_fee" 
                                           value="<?php echo htmlspecialchars($doctor['consultation_fee'] ?? ''); ?>" step="0.01">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($doctor['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Qualification *</label>
                                <input type="text" class="form-control" name="qualification" 
                                       value="<?php echo htmlspecialchars($doctor['qualification'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Specialization *</label>
                                    <input type="text" class="form-control" name="specialization" 
                                           value="<?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Years of Experience</label>
                                    <input type="number" class="form-control" name="experience" 
                                           value="<?php echo htmlspecialchars($doctor['experience_years'] ?? 0); ?>" min="0" max="50">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bio / Introduction</label>
                                <textarea class="form-control" name="bio" rows="4"><?php echo htmlspecialchars($doctor['bio'] ?? ''); ?></textarea>
                                <small class="text-muted">Tell patients about your expertise and approach</small>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Account Settings -->
            <div class="col-md-6">
                <!-- Change Password -->
                <div class="card form-card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-key"></i> Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="card form-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Account Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Account Status:</th>
                                <td>
                                    <?php 
                                    $status = $conn->query("SELECT is_verified, is_active FROM users WHERE id = $user_id")->fetch_assoc();
                                    if($status['is_verified'] && $status['is_active']) {
                                        echo '<span class="badge bg-success">Verified & Active</span>';
                                    } elseif(!$status['is_verified']) {
                                        echo '<span class="badge bg-warning">Pending Verification</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Inactive</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Member Since:</th>
                                <td><?php echo date('F d, Y', strtotime($doctor['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Last Login:</th>
                                <td><?php echo date('F d, Y H:i', $_SESSION['last_login'] ?? time()); ?></td>
                            </tr>
                            <tr>
                                <th>Account ID:</th>
                                <td>DR-<?php echo str_pad($doctor['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Danger Zone -->
                <div class="card form-card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Once you delete your account, there is no going back. Please be certain.</p>
                        <button class="btn btn-outline-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash-alt"></i> Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <p class="mb-0 text-muted">© 2024 MedAppoint - Doctor Profile</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete() {
            if(confirm('⚠️ Are you sure you want to delete your account?\nThis action cannot be undone!')) {
                if(confirm('This will permanently delete all your data. Type DELETE to confirm:')) {
                    const confirmation = prompt('Type DELETE to confirm:');
                    if(confirmation === 'DELETE') {
                        alert('Account deletion requested. This feature would delete your account.');
                        // In real implementation, you would make an AJAX call here
                    }
                }
            }
        }
        
        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if(!field.value.trim()) {
                        valid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if(!valid) {
                    e.preventDefault();
                    alert('Please fill all required fields (marked with *)');
                }
            });
        });
    </script>
</body>
</html>