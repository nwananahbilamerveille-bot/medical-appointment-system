<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $address = $_POST['address'] ?? '';
    
    $conn = getConnection();
    
    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "Email already registered!";
    } else {
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (role, full_name, email, password, phone, address) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $role, $full_name, $email, $password, $phone, $address);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // If registering as doctor
            if ($role === 'doctor') {
                $qualification = $_POST['qualification'] ?? '';
                $experience = $_POST['experience'] ?? 0;
                $specialization = $_POST['specialization'] ?? '';
                $bio = $_POST['bio'] ?? '';
                
                $doctor_stmt = $conn->prepare("INSERT INTO doctors (user_id, qualification, experience_years, specialization, bio) 
                                             VALUES (?, ?, ?, ?, ?)");
                $doctor_stmt->bind_param("isiss", $user_id, $qualification, $experience, $specialization, $bio);
                $doctor_stmt->execute();
                
                // Mark as pending verification
                $conn->query("UPDATE users SET is_verified = 0 WHERE id = $user_id");
            }
            
            // Auto login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            $_SESSION['full_name'] = $full_name;
            
            // Redirect
            if ($role === 'doctor') {
                header("Location: ../doctor/dashboard.php?new=1");
            } else {
                header("Location: ../patient/dashboard.php?new=1");
            }
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Doctor Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .register-container {
            max-width: 700px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .role-option {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .role-option:hover {
            transform: translateY(-5px);
            border-color: #007bff;
        }
        .role-option.active {
            border-color: #007bff;
            background-color: #f0f8ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container mx-auto">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Create Your Account</h2>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- Role Selection -->
                    <div class="mb-4">
                        <label class="form-label mb-2">I am a:</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="role-option card text-center p-4" data-role="patient">
                                    <div class="display-4 mb-2">👤</div>
                                    <h5>Patient</h5>
                                    <p class="text-muted small">Book appointments with doctors</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="role-option card text-center p-4" data-role="doctor">
                                    <div class="display-4 mb-2">👨‍⚕️</div>
                                    <h5>Doctor</h5>
                                    <p class="text-muted small">Manage appointments & patients</p>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="role" id="selectedRole" value="patient" required>
                    </div>
                    
                    <form method="POST" id="registerForm">
                        <input type="hidden" name="role" id="formRole" value="patient">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                        
                        <!-- Doctor Specific Fields -->
                        <div id="doctorFields" style="display: none;">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6>Doctor Information</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Qualification *</label>
                                                <input type="text" class="form-control" name="qualification">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Years of Experience</label>
                                                <input type="number" class="form-control" name="experience" min="0" max="50">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Specialization</label>
                                        <input type="text" class="form-control" name="specialization" 
                                               placeholder="e.g., Cardiologist, Dentist">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bio / Introduction</label>
                                        <textarea class="form-control" name="bio" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
                        
                        <div class="text-center mt-3">
                            <a href="login.php">Already have an account? Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Role selection
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from all
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                
                // Add active class to clicked
                this.classList.add('active');
                
                // Update hidden input
                const role = this.getAttribute('data-role');
                document.getElementById('formRole').value = role;
                
                // Show/hide doctor fields
                document.getElementById('doctorFields').style.display = 
                    role === 'doctor' ? 'block' : 'none';
            });
        });
        
        // Set patient as default active
        document.querySelector('[data-role="patient"]').classList.add('active');
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const role = document.getElementById('formRole').value;
            
            if(role === 'doctor') {
                const qualification = document.querySelector('input[name="qualification"]').value;
                if(!qualification.trim()) {
                    e.preventDefault();
                    alert('Please enter your qualification');
                    return false;
                }
            }
            return true;
        });
    </script>
</body>
</html>