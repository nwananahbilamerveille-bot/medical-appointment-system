<?php
session_start();
require_once '../config/functions.php';
requireLogin();

if (getCurrentUserRole() !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$conn = getConnection();

// Handle add specialization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_specialization'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    // Check if exists
    $check = $conn->prepare("SELECT id FROM specializations WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "Specialization already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO specializations (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $success = "Specialization added successfully!";
        } else {
            $error = "Error adding specialization.";
        }
    }
}

// Handle edit/delete
if (isset($_GET['action'])) {
    $id = $_GET['id'];
    
    if ($_GET['action'] === 'delete') {
        $conn->query("DELETE FROM specializations WHERE id = $id");
        $message = "Specialization deleted!";
    } elseif ($_GET['action'] === 'edit') {
        // Handle edit via POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            
            $stmt = $conn->prepare("UPDATE specializations SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $id);
            $stmt->execute();
            
            $message = "Specialization updated!";
        }
    }
}

// Get all specializations
$specializations = $conn->query("SELECT * FROM specializations ORDER BY name");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Specializations - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar-admin.php'; ?>
    
    <div class="container mt-4">
        <h2>Manage Specializations</h2>
        
        <?php if(isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Add Specialization Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Add New Specialization</h5>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Specialization Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" name="add_specialization" class="btn btn-primary w-100">
                                Add Specialization
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Specializations List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Specializations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Doctors</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($spec = $specializations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $spec['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($spec['name']); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($spec['description'] ?: 'No description'); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $doctor_count = $conn->query("
                                                    SELECT COUNT(*) as count FROM doctor_specializations 
                                                    WHERE specialization_id = {$spec['id']}
                                                ")->fetch_assoc()['count'];
                                                ?>
                                                <span class="badge bg-info"><?php echo $doctor_count; ?> doctors</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editSpecialization(<?php echo $spec['id']; ?>)">
                                                        ✏️ Edit
                                                    </button>
                                                    <a href="?action=delete&id=<?php echo $spec['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Delete this specialization?')">
                                                       🗑️ Delete
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
    
    <script>
    function editSpecialization(id) {
        // AJAX call to get specialization details and show edit form
        fetch(`get-specialization.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                // Create edit modal
                const modal = `
                    <div class="modal fade" id="editModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="?action=edit&id=${id}">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Specialization</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" 
                                                   value="${data.name}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="3">${data.description || ''}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modal);
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
                
                // Remove modal after hiding
                document.getElementById('editModal').addEventListener('hidden.bs.modal', function () {
                    this.remove();
                });
            });
    }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>