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

$doctor_id = $_GET['doctor_id'] ?? 0;
$conn = getConnection();

// Get doctor details
$doctor_stmt = $conn->prepare("
    SELECT d.*, u.full_name, u.email, u.phone 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.id = ? AND u.is_verified = 1
");
$doctor_stmt->bind_param("i", $doctor_id);
$doctor_stmt->execute();
$doctor = $doctor_stmt->get_result()->fetch_assoc();

if (!$doctor) {
    header("Location: search-doctors.php");
    exit();
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_date = sanitize($_POST['appointment_date']);
    $appointment_time = sanitize($_POST['appointment_time']);
    $symptoms = sanitize($_POST['symptoms']);
    
    // Check if slot is available
    $check = $conn->prepare("
        SELECT id FROM appointments 
        WHERE doctor_id = ? 
        AND appointment_date = ? 
        AND appointment_time = ?
        AND status NOT IN ('cancelled')
    ");
    $check->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "This time slot is already booked!";
    } else {
        // Create appointment
        $stmt = $conn->prepare("
            INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, symptoms)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisss", $_SESSION['user_id'], $doctor_id, $appointment_date, $appointment_time, $symptoms);
        
        if ($stmt->execute()) {
            $appointment_id = $stmt->insert_id;
            header("Location: booking-confirmation.php?id=" . $appointment_id);
            exit();
        } else {
            $error = "Error booking appointment. Please try again.";
        }
    }
}

// Get available slots for selected date
if (isset($_GET['date'])) {
    $date = $_GET['date'];
    
    // Get doctor's availability for that day
    $day_of_week = date('l', strtotime($date));
    
    $availability_stmt = $conn->prepare("
        SELECT * FROM availability 
        WHERE doctor_id = ? AND day_of_week = ?
    ");
    $availability_stmt->bind_param("is", $doctor_id, $day_of_week);
    $availability_stmt->execute();
    $availability = $availability_stmt->get_result()->fetch_assoc();
    
    $available_slots = [];
    
    if ($availability) {
        // Generate time slots
        $start = strtotime($availability['start_time']);
        $end = strtotime($availability['end_time']);
        $duration = $availability['slot_duration'] * 60; // Convert to seconds
        
        while ($start < $end) {
            $time_slot = date('H:i', $start);
            
            // Check if slot is already booked
            $booked_check = $conn->prepare("
                SELECT id FROM appointments 
                WHERE doctor_id = ? 
                AND appointment_date = ? 
                AND appointment_time = ?
                AND status NOT IN ('cancelled')
            ");
            $booked_check->bind_param("iss", $doctor_id, $date, $time_slot);
            $booked_check->execute();
            
            if ($booked_check->get_result()->num_rows === 0) {
                $available_slots[] = $time_slot;
            }
            
            $start += $duration;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Book Appointment with Dr. <?php echo $doctor['full_name']; ?></h4>
                    </div>
                    <div class="card-body">
                        <!-- Doctor Info -->
                        <div class="alert alert-info">
                            <strong>Specialization:</strong> <?php echo $doctor['specialization']; ?><br>
                            <strong>Qualification:</strong> <?php echo $doctor['qualification']; ?><br>
                            <?php if($doctor['consultation_fee']): ?>
                                <strong>Consultation Fee:</strong> XAF <?php echo number_format($doctor['consultation_fee'], 0); ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="bookingForm">
                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Select Date</label>
                                <input type="date" class="form-control" id="appointment_date" 
                                       name="appointment_date" required 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       onchange="loadAvailableSlots()">
                            </div>
                            
                            <div class="mb-3">
                                <label for="appointment_time" class="form-label">Select Time Slot</label>
                                <select class="form-control" id="appointment_time" name="appointment_time" required>
                                    <option value="">Select a date first</option>
                                    <?php if(isset($available_slots)): ?>
                                        <?php foreach($available_slots as $slot): ?>
                                            <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted" id="slotMessage"></small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="symptoms" class="form-label">Symptoms (Optional)</label>
                                <textarea class="form-control" id="symptoms" name="symptoms" 
                                          rows="3" placeholder="Briefly describe your symptoms"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Confirm Booking</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function loadAvailableSlots() {
            const date = document.getElementById('appointment_date').value;
            if (!date) return;
            
            const doctorId = <?php echo $doctor_id; ?>;
            const timeSelect = document.getElementById('appointment_time');
            const message = document.getElementById('slotMessage');
            
            timeSelect.innerHTML = '<option value="">Loading...</option>';
            message.textContent = '';
            
            fetch(`get-available-slots.php?doctor_id=${doctorId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    timeSelect.innerHTML = '';
                    
                    if (data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot;
                            option.textContent = slot;
                            timeSelect.appendChild(option);
                        });
                        message.textContent = `${data.slots.length} slot(s) available`;
                        message.className = 'text-muted';
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'No slots available';
                        timeSelect.appendChild(option);
                        message.textContent = 'No available slots for this date';
                        message.className = 'text-danger';
                    }
                });
        }
    </script>
</body>
</html>