<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'public');

session_start();
require_once '../config/functions.php';
requireLogin();

if (getCurrentUserRole() !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$appointment_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$conn = getConnection();

// Get appointment details
$stmt = $conn->prepare("
    SELECT a.*, d.specialization, d.consultation_fee,
           u.full_name as doctor_name, u.email as doctor_email, u.phone as doctor_phone,
           u2.full_name as patient_name, u2.email as patient_email, u2.phone as patient_phone
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN users u2 ON a.patient_id = u2.id
    WHERE a.id = ? AND a.patient_id = ?
");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .confirmation-card {
            max-width: 600px;
            margin: 50px auto;
            border: 2px solid #28a745;
        }
        .header-success {
            background: #28a745;
            color: white;
        }
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar-patient.php'; ?>
    
    <div class="container">
        <div class="card confirmation-card shadow">
            <div class="card-header header-success text-center">
                <h2 class="mb-0">✅ Booking Confirmed!</h2>
            </div>
            
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="display-1 text-success mb-3">✓</div>
                    <h4>Your appointment has been booked successfully!</h4>
                    <p class="text-muted">Appointment ID: #<?php echo str_pad($appointment_id, 6, '0', STR_PAD_LEFT); ?></p>
                </div>
                
                <hr>
                
                <!-- Appointment Details -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>Doctor Details</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <strong>Dr. <?php echo $appointment['doctor_name']; ?></strong><br>
                                <small class="text-muted"><?php echo $appointment['specialization']; ?></small><br>
                                <small>📧 <?php echo $appointment['doctor_email']; ?></small><br>
                                <small>📞 <?php echo $appointment['doctor_phone']; ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Appointment Details</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <strong>Date:</strong> <?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?><br>
                                <strong>Time:</strong> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?><br>
                                <strong>Status:</strong> <span class="badge bg-warning"><?php echo ucfirst($appointment['status']); ?></span><br>
                                <?php if($appointment['consultation_fee']): ?>
                                    <strong>Fee:</strong> XAF <?php echo number_format($appointment['consultation_fee'], 0); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Patient Details -->
                <div class="mt-4">
                    <h5>Your Details</h5>
                    <div class="card">
                        <div class="card-body">
                            <strong><?php echo $appointment['patient_name']; ?></strong><br>
                            <small>📧 <?php echo $appointment['patient_email']; ?></small><br>
                            <small>📞 <?php echo $appointment['patient_phone']; ?></small>
                        </div>
                    </div>
                </div>
                
                <?php if($appointment['symptoms']): ?>
                    <div class="mt-4">
                        <h5>Reported Symptoms</h5>
                        <div class="card">
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($appointment['symptoms'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Important Notes -->
                <div class="alert alert-info mt-4">
                    <h6>📋 Important Notes:</h6>
                    <ul class="mb-0">
                        <li>Please arrive 10 minutes before your appointment</li>
                        <li>Bring your ID and insurance card (if applicable)</li>
                        <li>Cancel at least 24 hours in advance if needed</li>
                        <li>Contact the clinic if you have any questions</li>
                    </ul>
                </div>
                
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <a href="appointments.php" class="btn btn-outline-primary">View All Appointments</a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        🖨️ Print Confirmation
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to top for better print view
        window.onload = function() {
            window.scrollTo(0, 0);
        }
    </script>
</body>
</html>