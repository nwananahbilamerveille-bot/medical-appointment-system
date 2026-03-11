<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once '../config/functions.php';
requireLogin();

if (getCurrentUserRole() !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$conn = getConnection();

/* ===============================
   SEARCH VALUES
================================ */
$specialization = $_POST['specialization'] ?? '';
$doctor_name    = $_POST['doctor_name'] ?? '';

/* ===============================
   SQL QUERY
================================ */
$query = "
    SELECT d.id, d.specialization, d.qualification, d.consultation_fee,
           u.full_name, u.phone, u.address,
           GROUP_CONCAT(s.name SEPARATOR ', ') as specializations
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN doctor_specializations ds ON d.id = ds.doctor_id
    LEFT JOIN specializations s ON ds.specialization_id = s.id
    WHERE u.is_active = 1 AND u.is_verified = 1
";

$conditions = [];
$params = [];
$types = "";

/* ===============================
   FILTERS
================================ */
if (!empty($specialization)) {
    $conditions[] = "s.id = ?";
    $params[] = $specialization;
    $types .= "i";
}

if (!empty($doctor_name)) {
    $conditions[] = "u.full_name LIKE ?";
    $params[] = "%$doctor_name%";
    $types .= "s";
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " GROUP BY d.id";

/* ===============================
   EXECUTE QUERY
================================ */
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$search_results = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Find Doctors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">

    <h2 class="mb-4">Find Doctors</h2>

    <!-- =========================
         SEARCH FORM
    ========================== -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Specialization</label>
                    <select name="specialization" class="form-select">
                        <option value="">All Specializations</option>
                        <?php foreach(getSpecializations() as $spec): ?>
                            <option value="<?= $spec['id']; ?>"
                                <?= ($specialization == $spec['id']) ? 'selected' : ''; ?>>
                                <?= $spec['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Doctor Name</label>
                    <input type="text"
                           name="doctor_name"
                           class="form-control"
                           placeholder="Search by doctor name"
                           value="<?= htmlspecialchars($doctor_name); ?>">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        Search Doctors
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- =========================
         DOCTOR LIST
    ========================== -->
    <h4 class="mb-3">Available Doctors</h4>

    <?php if ($search_results->num_rows > 0): ?>

        <div class="row">

            <?php while($doctor = $search_results->fetch_assoc()): ?>

                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">

                            <h5 class="card-title">
                                Dr. <?= htmlspecialchars($doctor['full_name']); ?>
                            </h5>

                            <h6 class="card-subtitle mb-2 text-muted">
                                <?= $doctor['specializations'] ?: $doctor['specialization']; ?>
                            </h6>

                            <p class="card-text">
                                <small><?= htmlspecialchars($doctor['qualification']); ?></small><br>

                                <?php if ($doctor['consultation_fee']): ?>
                                    <strong>
                                        Fee: XAF <?= number_format($doctor['consultation_fee'], 0); ?>
                                    </strong>
                                <?php endif; ?>
                            </p>

                            <p class="text-muted">
                                <?= htmlspecialchars($doctor['address']); ?>
                            </p>

                            <!-- BOOK BUTTON -->
                            <a href="book-appointment.php?doctor_id=<?= $doctor['id']; ?>"
                               class="btn btn-success btn-sm">
                                Book Appointment
                            </a>

                        </div>
                    </div>
                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="alert alert-info">
            No doctors found in the system.
        </div>

    <?php endif; ?>

</div>

</body>
</html>
