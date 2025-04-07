<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'navbar.php';

// Handle form submission for inserting data into location_inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $locationId = $_POST['location_id'];
    $employeeId = $_POST['employee_id'];
    $fishTypeId = $_POST['fish_type_id'];
    $quantity = $_POST['quantity'];

    $stmt = $pdo->prepare("
        INSERT INTO location_inventory (location_id, employee_id, fish_type_id, quantity)
        VALUES (:location_id, :employee_id, :fish_type_id, :quantity)
        ON DUPLICATE KEY UPDATE quantity = :quantity
    ");
    $stmt->execute([
        ':location_id' => $locationId,
        ':employee_id' => $employeeId,
        ':fish_type_id' => $fishTypeId,
        ':quantity' => $quantity
    ]);

    // Redirect to avoid form resubmission
    header("Location: location_management.php");
    exit();
}

// Handle form submission for adding new location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];

    $stmt = $pdo->prepare("
        INSERT INTO locations (name, address)
        VALUES (:name, :address)
    ");
    $stmt->execute([
        ':name' => $name,
        ':address' => $address
    ]);

    // Redirect to avoid form resubmission
    header("Location: location_management.php");
    exit();
}

// Get all locations
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();

// Get all employees
$employees = $pdo->query("SELECT * FROM users WHERE role = 'employee'")->fetchAll();

// Get all fish types
$fishTypes = $pdo->query("SELECT * FROM fish_types")->fetchAll();

// Get current inventory assignments
$inventoryAssignments = $pdo->query("
    SELECT li.*, l.name as location_name, u.username as employee_name, ft.name as fish_type_name
    FROM location_inventory li
    JOIN locations l ON li.location_id = l.id
    JOIN users u ON li.employee_id = u.id
    JOIN fish_types ft ON li.fish_type_id = ft.id
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .section-title {
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="my-4">Location Management</h1>

        <!-- Add New Location Form -->
        <div class="form-container">
            <h3>Add New Location</h3>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Location Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" name="add_location" class="btn btn-primary">Add Location</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Assignment Form -->
        <h3 class="section-title">Inventory Assignment</h3>
        <div class="form-container">
            <h4>Add/Update Inventory Assignment</h4>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="location_id" class="form-label">Location</label>
                        <select class="form-select" id="location_id" name="location_id" required>
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= htmlspecialchars($location['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select class="form-select" id="employee_id" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="fish_type_id" class="form-label">Fish Type</label>
                        <select class="form-select" id="fish_type_id" name="fish_type_id" required>
                            <option value="">Select Fish Type</option>
                            <?php foreach ($fishTypes as $fishType): ?>
                                <option value="<?= $fishType['id'] ?>"><?= htmlspecialchars($fishType['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="quantity" class="form-label">Quantity (kg)</label>
                        <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" name="add_assignment" class="btn btn-primary">Save Assignment</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Current Assignments Table -->
        <h3 class="section-title">Current Inventory Assignments</h3>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Location</th>
                        <th>Employee</th>
                        <th>Fish Type</th>
                        <th>Quantity (kg)</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventoryAssignments as $assignment): ?>
                        <tr>
                            <td><?= htmlspecialchars($assignment['location_name']) ?></td>
                            <td><?= htmlspecialchars($assignment['employee_name']) ?></td>
                            <td><?= htmlspecialchars($assignment['fish_type_name']) ?></td>
                            <td><?= number_format($assignment['quantity'], 2) ?></td>
                            <td><?= date('M j, Y g:i a', strtotime($assignment['last_updated'])) ?></td>
                            <td>
                                <form method="POST" action="delete_assignment.php" style="display: inline;">
                                    <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this assignment?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>