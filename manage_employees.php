<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        // Add new employee
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];

        try {
            $pdo->beginTransaction();

            // Add user with employee role
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'employee')");
            $stmt->execute([$username, $password, $email]);
            $userId = $pdo->lastInsertId();

            // Set default permissions
            $stmt = $pdo->prepare("INSERT INTO employee_permissions (user_id) VALUES (?)");
            $stmt->execute([$userId]);

            $pdo->commit();
            $_SESSION['message'] = "Employee added successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error adding employee: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_permissions'])) {
        // Update employee permissions
        $userId = $_POST['user_id'];
        $canSell = isset($_POST['can_sell']) ? 1 : 0;
        $canRecordFatality = isset($_POST['can_record_fatality']) ? 1 : 0;
        $canProcessOrders = isset($_POST['can_process_orders']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE employee_permissions SET 
                can_sell = ?, 
                can_record_fatality = ?, 
                can_process_orders = ? 
                WHERE user_id = ?");
            $stmt->execute([$canSell, $canRecordFatality, $canProcessOrders, $userId]);
            $_SESSION['message'] = "Permissions updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating permissions: " . $e->getMessage();
        }
    }
}

// Get all employees
$employees = $pdo->query("
    SELECT u.id, u.username, u.email, u.created_at, 
           ep.can_sell, ep.can_record_fatality, ep.can_process_orders
    FROM users u
    LEFT JOIN employee_permissions ep ON u.id = ep.user_id
    WHERE u.role = 'employee'
    ORDER BY u.username
")->fetchAll();

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Manage Employees</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Add New Employee</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Employee Permissions</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($employees)): ?>
                            <p>No employees found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Can Sell</th>
                                            <th>Record Fatality</th>
                                            <th>Process Orders</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($employee['username']) ?></td>
                                                <td><?= $employee['can_sell'] ? '✅' : '❌' ?></td>
                                                <td><?= $employee['can_record_fatality'] ? '✅' : '❌' ?></td>
                                                <td><?= $employee['can_process_orders'] ? '✅' : '❌' ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#editPermissionsModal"
                                                        data-userid="<?= $employee['id'] ?>"
                                                        data-username="<?= htmlspecialchars($employee['username']) ?>"
                                                        data-can-sell="<?= $employee['can_sell'] ?>"
                                                        data-can-fatality="<?= $employee['can_record_fatality'] ?>"
                                                        data-can-orders="<?= $employee['can_process_orders'] ?>">
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Permissions Modal -->
    <div class="modal fade" id="editPermissionsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Permissions for <span id="modalEmployeeName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="modalUserId" name="user_id">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="canSell" name="can_sell">
                            <label class="form-check-label" for="canSell">Can Sell Products</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="canRecordFatality" name="can_record_fatality">
                            <label class="form-check-label" for="canRecordFatality">Can Record Fatalities</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="canProcessOrders" name="can_process_orders">
                            <label class="form-check-label" for="canProcessOrders">Can Process Customer Orders</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_permissions" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize modal with employee data
        document.getElementById('editPermissionsModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modal = this;

            modal.querySelector('#modalEmployeeName').textContent = button.getAttribute('data-username');
            modal.querySelector('#modalUserId').value = button.getAttribute('data-userid');
            modal.querySelector('#canSell').checked = button.getAttribute('data-can-sell') === '1';
            modal.querySelector('#canRecordFatality').checked = button.getAttribute('data-can-fatality') === '1';
            modal.querySelector('#canProcessOrders').checked = button.getAttribute('data-can-orders') === '1';
        });
    </script>
</body>

</html>