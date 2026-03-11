<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once '../config/functions.php';
requireLogin();

if (getCurrentUserRole() !== 'doctor') {
    header("Location: ../index.php");
    exit();
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

/* ==============================
   GET DOCTOR ID
============================== */
$doctor_stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$doctor_stmt->bind_param("i", $user_id);
$doctor_stmt->execute();
$doctor_result = $doctor_stmt->get_result();

if ($doctor_result->num_rows === 0) {
    die("Doctor profile not found.");
}

$doctor_id = $doctor_result->fetch_assoc()['id'];

/* ==============================
   UPDATE STATUS
============================== */
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    $update_stmt = $conn->prepare(
        "UPDATE appointments 
         SET status = ? 
         WHERE id = ? AND doctor_id = ?"
    );
    $update_stmt->bind_param("sii", $status, $appointment_id, $doctor_id);
    $update_stmt->execute();

    $message = "Appointment status updated successfully!";
}

/* ==============================
   FETCH APPOINTMENTS
============================== */
$today = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT a.*, u.full_name AS patient_name,
           u.email, u.phone
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date ASC,
             a.appointment_time ASC
");

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

/* Store appointments in array */
$appointments = [];
$today_appointments = [];

while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;

    if ($row['appointment_date'] === $today) {
        $today_appointments[] = $row;
    }
}

/* ==============================
   STATISTICS
============================== */
$total = count($appointments);

$completed = 0;
$upcoming = 0;

foreach ($appointments as $app) {
    if ($app['status'] === 'completed') {
        $completed++;
    }

    if ($app['appointment_date'] > $today &&
        in_array($app['status'], ['scheduled','confirmed'])) {
        $upcoming++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">

<?php if(isset($message)): ?>
<div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<div class="row">

<!-- TODAY -->
<div class="col-md-6">
<div class="card border-primary">
<div class="card-header bg-primary text-white">
Today's Appointments
</div>
<div class="card-body">

<?php if(!empty($today_appointments)): ?>
<?php foreach($today_appointments as $app): ?>
<div class="border p-2 mb-2">
<strong><?= htmlspecialchars($app['patient_name']) ?></strong><br>
<?= date('h:i A', strtotime($app['appointment_time'])) ?><br>

<form method="POST">
<input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
<select name="status" onchange="this.form.submit()" class="form-select form-select-sm mt-2">
<option value="scheduled" <?= $app['status']=='scheduled'?'selected':'' ?>>Scheduled</option>
<option value="confirmed" <?= $app['status']=='confirmed'?'selected':'' ?>>Confirmed</option>
<option value="completed" <?= $app['status']=='completed'?'selected':'' ?>>Completed</option>
<option value="cancelled" <?= $app['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
</select>
<input type="hidden" name="update_status" value="1">
</form>

</div>
<?php endforeach; ?>
<?php else: ?>
<p>No appointments today.</p>
<?php endif; ?>

</div>
</div>
</div>

<!-- ALL -->
<div class="col-md-6">
<div class="card">
<div class="card-header">
All Appointments
</div>
<div class="card-body">

<?php if(!empty($appointments)): ?>
<table class="table table-sm">
<thead>
<tr>
<th>Patient</th>
<th>Date</th>
<th>Time</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($appointments as $app): ?>
<tr>
<td><?= htmlspecialchars($app['patient_name']) ?></td>
<td><?= $app['appointment_date'] ?></td>
<td><?= date('h:i A', strtotime($app['appointment_time'])) ?></td>
<td><?= ucfirst($app['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>No appointments found.</p>
<?php endif; ?>

</div>
</div>
</div>

</div>

<!-- STATISTICS -->
<div class="row mt-4">
<div class="col-md-3">
<div class="card bg-info text-white p-3">
Today<br>
<h4><?= count($today_appointments) ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card bg-success text-white p-3">
Completed<br>
<h4><?= $completed ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card bg-warning text-white p-3">
Upcoming<br>
<h4><?= $upcoming ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card bg-secondary text-white p-3">
Total<br>
<h4><?= $total ?></h4>
</div>
</div>
</div>

</div>
</body>
</html>
