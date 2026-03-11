<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields!";
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, password, role, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Invalid email or password!";
        } else {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Redirect based on role
                switch($user['role']) {
                    case 'patient':
                        header("Location: ../patient/dashboard.php");
                        break;
                    case 'doctor':
                        header("Location: ../doctor/dashboard.php");
                        break;
                    case 'admin':
                        header("Location: ../admin/dashboard.php");
                        break;
                    default:
                        header("Location: ../index.php");
                }
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Doctor Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-body {
            padding: 40px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            font-weight: 500;
            transition: transform 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .text-center a {
            color: #667eea;
            font-weight: 500;
        }
        .text-center a:hover {
            color: #764ba2;
        }
        .demo-buttons .btn {
            font-size: 12px;
        }
        hr {
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">👨‍⚕️ Doctor Appointment System</h2>
                    <p class="text-center text-muted mb-4">Sign in to your account</p>
                    
                    <?php if(isset($error) && !empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" required 
                                   placeholder="Enter your email">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required 
                                   placeholder="Enter your password">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
                        
                        <div class="text-center mt-3">
                            <a href="register.php" class="text-decoration-none">Don't have an account? Register</a>
                        </div>
                        
                        <div class="text-center mt-2">
                            <a href="#" class="text-decoration-none">Forgot password?</a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <small class="text-muted">Login as:</small>
                        <div class="d-flex justify-content-center gap-2 mt-2">
                            <a href="?demo=patient" class="btn btn-sm btn-outline-primary">Patient</a>
                            <a href="?demo=doctor" class="btn btn-sm btn-outline-success">Doctor</a>
                            <a href="?demo=admin" class="btn btn-sm btn-outline-danger">Admin</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Demo login credentials
        const demoCredentials = {
            'patient': {email: 'patient@demo.com', password: 'demo123'},
            'doctor': {email: 'doctor@demo.com', password: 'demo123'},
            'admin': {email: 'admin@demo.com', password: 'demo123'}
        };
        
        // Auto-fill demo credentials
        const urlParams = new URLSearchParams(window.location.search);
        const demoRole = urlParams.get('demo');
        
        if(demoRole && demoCredentials[demoRole]) {
            document.querySelector('input[name="email"]').value = demoCredentials[demoRole].email;
            document.querySelector('input[name="password"]').value = demoCredentials[demoRole].password;
        }
    </script>
</body>
</html>