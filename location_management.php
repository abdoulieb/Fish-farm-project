<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'navbar.php';

// Permission check
if (!isAdmin() && !canEmployeeSell()) {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: index.php");
    exit();
}

// Handle form submission for inserting data into location_inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    if (!isAdmin()) {
        $_SESSION['error'] = "Only admins can create assignments";
        header("Location: location_management.php");
        exit();
    }

    $locationId = $_POST['location_id'];
    $employeeId = $_POST['employee_id'];
    $fishTypeId = $_POST['fish_type_id'];
    $quantity = floatval($_POST['quantity']);

    try {
        $pdo->beginTransaction();

        // Check available inventory
        $stmt = $pdo->prepare("SELECT quantity_kg FROM inventory WHERE fish_type_id = ?");
        $stmt->execute([$fishTypeId]);
        $inventory = $stmt->fetch();

        if (!$inventory) {
            throw new Exception("Fish type not found in inventory");
        }

        $availableKg = floatval($inventory['quantity_kg']);

        if ($quantity > $availableKg) {
            throw new Exception("Cannot assign more than available inventory. Available: $availableKg kg");
        }

        // Insert or update location assignment
        $stmt = $pdo->prepare("
            INSERT INTO location_inventory (location_id, employee_id, fish_type_id, quantity, status, last_assigned_quantity)
            VALUES (:location_id, :employee_id, :fish_type_id, :quantity, 'pending', :quantity)
            ON DUPLICATE KEY UPDATE 
                quantity = quantity + VALUES(quantity),
                last_assigned_quantity = VALUES(last_assigned_quantity),
                status = 'pending'
        ");
        $stmt->execute([
            ':location_id' => $locationId,
            ':employee_id' => $employeeId,
            ':fish_type_id' => $fishTypeId,
            ':quantity' => $quantity
        ]);

        // Update main inventory (subtract the assigned quantity)
        $stmt = $pdo->prepare("
            UPDATE inventory SET quantity_kg = quantity_kg - ? 
            WHERE fish_type_id = ?
        ");
        $stmt->execute([$quantity, $fishTypeId]);

        $pdo->commit();
        $_SESSION['message'] = "Inventory assigned successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: location_management.php");
    exit();
}

// Handle form submission for adding new location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    if (!isAdmin()) {
        $_SESSION['error'] = "Only admins can add locations";
        header("Location: location_management.php");
        exit();
    }

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

    header("Location: location_management.php");
    exit();
}

// Handle location deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
    if (!isAdmin()) {
        $_SESSION['error'] = "Only admins can delete locations";
        header("Location: location_management.php");
        exit();
    }

    $locationId = $_POST['location_id'];

    try {
        $pdo->beginTransaction();

        // First get all inventory assignments to this location to return quantities
        $stmt = $pdo->prepare("
            SELECT fish_type_id, SUM(quantity) as total_quantity 
            FROM location_inventory 
            WHERE location_id = ?
            GROUP BY fish_type_id
        ");
        $stmt->execute([$locationId]);
        $assignments = $stmt->fetchAll();

        // Return quantities to main inventory
        foreach ($assignments as $assignment) {
            $stmt = $pdo->prepare("
                UPDATE inventory SET quantity_kg = quantity_kg + ? 
                WHERE fish_type_id = ?
            ");
            $stmt->execute([$assignment['total_quantity'], $assignment['fish_type_id']]);
        }

        // Delete related inventory assignments
        $stmt = $pdo->prepare("DELETE FROM location_inventory WHERE location_id = :location_id");
        $stmt->execute([':location_id' => $locationId]);

        // Then delete the location
        $stmt = $pdo->prepare("DELETE FROM locations WHERE id = :id");
        $stmt->execute([':id' => $locationId]);

        $pdo->commit();
        $_SESSION['message'] = "Location deleted successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete location: " . $e->getMessage();
    }

    header("Location: location_management.php");
    exit();
}

// Get all locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

// Get all employees
$employees = $pdo->query("SELECT * FROM users WHERE role = 'employee' ORDER BY username")->fetchAll();

// Get all fish types with their available quantities
$fishTypes = $pdo->query("
    SELECT ft.*, i.quantity_kg as available_kg 
    FROM fish_types ft
    JOIN inventory i ON ft.id = i.fish_type_id
    ORDER BY ft.name
")->fetchAll();

// Get current inventory assignments
if (isAdmin()) {
    $inventoryAssignments = $pdo->query("
        SELECT li.*, l.name as location_name, u.username as employee_name, 
               ft.name as fish_type_name,
               CASE 
                   WHEN li.status = 'pending' THEN 'Pending Acceptance'
                   WHEN li.status = 'accepted' THEN 'Accepted'
                   WHEN li.status = 'rejected' THEN 'Rejected'
                   ELSE 'Unknown'
               END as status_text,
               (SELECT MAX(sale_date) FROM sales s 
                JOIN sale_items si ON s.id = si.sale_id
                WHERE s.employee_id = li.employee_id 
                AND si.fish_type_id = li.fish_type_id) as last_sale_date
        FROM location_inventory li
        JOIN locations l ON li.location_id = l.id
        JOIN users u ON li.employee_id = u.id
        JOIN fish_types ft ON li.fish_type_id = ft.id
        ORDER BY li.status, l.name, u.username, ft.name
    ")->fetchAll();
} else {
    $inventoryAssignments = $pdo->prepare("
        SELECT li.*, l.name as location_name, u.username as employee_name, 
               ft.name as fish_type_name,
               CASE 
                   WHEN li.status = 'pending' THEN 'Pending Acceptance'
                   WHEN li.status = 'accepted' THEN 'Accepted'
                   WHEN li.status = 'rejected' THEN 'Rejected'
                   ELSE 'Unknown'
               END as status_text,
               (SELECT MAX(sale_date) FROM sales s 
                JOIN sale_items si ON s.id = si.sale_id
                WHERE s.employee_id = li.employee_id 
                AND si.fish_type_id = li.fish_type_id) as last_sale_date
        FROM location_inventory li
        JOIN locations l ON li.location_id = l.id
        JOIN users u ON li.employee_id = u.id
        JOIN fish_types ft ON li.fish_type_id = ft.id
        WHERE li.employee_id = ?
        ORDER BY li.status, l.name, ft.name
    ");
    $inventoryAssignments->execute([$_SESSION['user_id']]);
    $inventoryAssignments = $inventoryAssignments->fetchAll();
}
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
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .section-title {
            margin-top: 40px;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
            color: #2c3e50;
            font-weight: 600;
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
        }

        .btn-action {
            margin-right: 5px;
            min-width: 80px;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .table th {
            background-color: #2c3e50;
            color: white;
            font-weight: 500;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(44, 62, 80, 0.05);
        }

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-accepted {
            background-color: #28a745;
            color: #fff;
        }

        .badge-rejected {
            background-color: #dc3545;
            color: #fff;
        }

        .available-quantity {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="my-4 text-primary">Location Management</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <!-- Add New Location Form -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="form-container">
                        <h3 class="card-title"><i class="fas fa-map-marker-alt me-2"></i>Add New Location</h3>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Location Name</label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Enter location name">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required placeholder="Enter full address"></textarea>
                            </div>
                            <button type="submit" name="add_location" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add Location
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Locations List -->
                <div class="col-lg-6">
                    <div class="form-container">
                        <h3 class="card-title"><i class="fas fa-list me-2"></i>Locations List</h3>
                        <?php if (count($locations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Address</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locations as $location): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($location['name']) ?></td>
                                                <td><?= htmlspecialchars($location['address']) ?></td>
                                                <td class="action-buttons">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                                        <button type="submit" name="delete_location" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this location and all its assignments?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No locations found. Add your first location above.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <!-- Assignment Form -->
            <h3 class="section-title"><i class="fas fa-fish me-2"></i>Inventory Assignment</h3>
            <div class="form-container">
                <h4 class="card-title"><i class="fas fa-plus-circle me-2"></i>Add/Update Inventory Assignment</h4>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="location_id" class="form-label">Location</label>
                            <select class="form-select" id="location_id" name="location_id" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location['id'] ?>"><?= htmlspecialchars($location['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="fish_type_id" class="form-label">Fish Type</label>
                            <select class="form-select" id="fish_type_id" name="fish_type_id" required>
                                <option value="">Select Fish Type</option>
                                <?php foreach ($fishTypes as $fishType): ?>
                                    <option value="<?= $fishType['id'] ?>" data-available="<?= $fishType['available_kg'] ?>">
                                        <?= htmlspecialchars($fishType['name']) ?>
                                        <span class="available-quantity">(Available: <?= number_format($fishType['available_kg'], 2) ?> kg)</span>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="quantity" class="form-label">Quantity (kg)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="quantity" name="quantity" required placeholder="0.00">
                                <span class="input-group-text">kg</span>
                            </div>
                            <small id="availableDisplay" class="text-muted"></small>
                        </div>
                        <div class="col-md-12 mt-3">
                            <button type="submit" name="add_assignment" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Assignment
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Current Assignments Table -->
        <h3 class="section-title"><i class="fas fa-clipboard-list me-2"></i>Current Inventory Assignments</h3>
        <div class="table-responsive">
            <?php if (count($inventoryAssignments) > 0): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Employee</th>
                            <th>Fish Type</th>
                            <th>Quantity (kg)</th>
                            <th>new added (kg)</th>
                            <th>Last Updated</th>
                            <th>Last Sale</th>
                            <th>Status</th>
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
                                <td><?= number_format($assignment['last_assigned_quantity'], 2) ?></td>
                                <td><?= date('M j, Y g:i a', strtotime($assignment['last_updated'])) ?></td>
                                <td><?= $assignment['last_sale_date'] ? date('M j, Y g:i a', strtotime($assignment['last_sale_date'])) : 'Never' ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($assignment['status_text']) ?>">
                                        <?= $assignment['status_text'] ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <?php if ($assignment['status'] == 'pending' && $_SESSION['user_id'] == $assignment['employee_id']): ?>
                                        <form method="POST" action="process_assignment.php" style="display: inline;">
                                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                        </form>
                                        <form method="POST" action="process_assignment.php" style="display: inline;">
                                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to reject this assignment? The quantity will be returned to main inventory.')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (isAdmin()): ?>
                                        <form method="POST" action="delete_assignment.php" style="display: inline;">
                                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this assignment? The quantity will be returned to main inventory.')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No inventory assignments found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on first input field
            document.querySelector('form input')?.focus();

            // Add confirmation for delete actions
            document.querySelectorAll('form[action="delete_assignment.php"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to delete this assignment?')) {
                        e.preventDefault();
                    }
                });
            });

            // Update available quantity display when fish type changes
            const fishTypeSelect = document.getElementById('fish_type_id');
            const quantityInput = document.getElementById('quantity');
            const availableDisplay = document.getElementById('availableDisplay');

            if (fishTypeSelect && quantityInput && availableDisplay) {
                fishTypeSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const availableKg = parseFloat(selectedOption.dataset.available) || 0;

                    availableDisplay.textContent = `Available: ${availableKg.toFixed(2)} kg`;
                    quantityInput.max = availableKg;

                    // Show warning if trying to assign more than available
                    quantityInput.addEventListener('input', function() {
                        const quantity = parseFloat(this.value) || 0;
                        if (quantity > availableKg) {
                            availableDisplay.innerHTML = `<span class="text-danger">Cannot assign more than available (${availableKg.toFixed(2)} kg)</span>`;
                        } else {
                            availableDisplay.textContent = `Available: ${availableKg.toFixed(2)} kg`;
                        }
                    });
                });

                // Trigger change event to initialize
                fishTypeSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>

</html>