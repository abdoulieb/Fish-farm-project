<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

$fishTypes = getAllFishTypes();
$inventory = getInventory();
$orders = getOrders();

// Get all employees for location assignment
$employees = $pdo->query("SELECT id, username FROM users WHERE role = 'employee'")->fetchAll();

// Get all locations
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();

// Get fatality summary
$fatalitySummary = $pdo->query("
    SELECT ft.name, 
           SUM(ff.quantity) as total_fatalities,
           (SELECT SUM(fingerlings_quantity) FROM detailed_costs dc WHERE dc.fish_type_id = ft.id) as total_fingerlings,
           (SELECT SUM(fingerlings_quantity) FROM detailed_costs dc WHERE dc.fish_type_id = ft.id) - SUM(ff.quantity) as live_count
    FROM fish_fatalities ff
    JOIN fish_types ft ON ff.fish_type_id = ft.id
    GROUP BY ff.fish_type_id
")->fetchAll();

// Get daily sales by employee
$dailySales = $pdo->query("
    SELECT u.username, 
           DATE(s.sale_date) as sale_day,
           COUNT(s.id) as sales_count,
           SUM(s.total_amount) as total_sales,
           SUM(si.quantity_kg) as total_kg
    FROM sales s
    JOIN users u ON s.employee_id = u.id
    JOIN sale_items si ON s.id = si.sale_id
    GROUP BY u.id, DATE(s.sale_date)
    ORDER BY sale_day DESC, u.username
")->fetchAll();

// Get current location assignments
$assignments = $pdo->query("
    SELECT l.name as location_name, u.username, ft.name as fish_name, li.quantity
    FROM location_inventory li
    JOIN locations l ON li.location_id = l.id
    JOIN users u ON li.employee_id = u.id
    JOIN fish_types ft ON li.fish_type_id = ft.id
    ORDER BY l.name, u.username
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fish Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tab-content {
            padding: 20px 0;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0 0 5px 5px;
        }

        .location-assignment {
            margin-bottom: 20px;
        }

        .badge {
            font-size: 0.9rem;
        }

        .card {
            margin-bottom: 20px;
        }

        .nav-tabs {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Fish Farm</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cost_management.php">Cost Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profit_analysis.php">Profit Analysis</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Admin Dashboard</h2>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">Inventory</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales Reports</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="locations-tab" data-bs-toggle="tab" data-bs-target="#locations" type="button" role="tab">Location Management</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fatality-tab" data-bs-toggle="tab" data-bs-target="#fatality" type="button" role="tab">Fatality Reports</button>
            </li>
            <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="manage_employees.php">Manage Employees</a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Inventory Tab -->
            <div class="tab-pane fade show active" id="inventory" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5>Inventory Management</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fish Type</th>
                                            <th>Quantity (kg)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inventory as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['name']) ?></td>
                                                <td><?= number_format($item['quantity_kg'], 2) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateInventoryModal"
                                                        data-id="<?= $item['fish_type_id'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>"
                                                        data-quantity="<?= $item['quantity_kg'] ?>">
                                                        Update
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5>Fish Types</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Price/kg</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fishTypes as $fish): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($fish['name']) ?></td>
                                                <td>D<?= number_format($fish['price_per_kg'], 2) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editFishModal"
                                                        data-id="<?= $fish['id'] ?>" data-name="<?= htmlspecialchars($fish['name']) ?>"
                                                        data-description="<?= htmlspecialchars($fish['description']) ?>"
                                                        data-price="<?= $fish['price_per_kg'] ?>">
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFishModal">
                                    Add New Fish Type
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5>All Orders</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= htmlspecialchars($order['username']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                        <td>D<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge 
                                <?= $order['status'] === 'completed' ? 'bg-success' : ($order['status'] === 'processing' ? 'bg-primary' : ($order['status'] === 'cancelled' ? 'bg-danger' : 'bg-secondary')) ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#orderDetailsModal"
                                                data-id="<?= $order['id'] ?>">
                                                Details
                                            </button>

                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    Change Status
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <!-- Convert all status change links to forms for POST requests -->
                                                    <li>
                                                        <form method="POST" action="update_order_status.php" style="display:inline;">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="status" value="pending">
                                                            <button type="submit" class="dropdown-item">Pending</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="update_order_status.php" style="display:inline;">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="status" value="processing">
                                                            <button type="submit" class="dropdown-item">Processing</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="update_order_status.php" style="display:inline;">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="status" value="completed">
                                                            <button type="submit" class="dropdown-item">Completed</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="update_order_status.php" style="display:inline;">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" class="dropdown-item text-danger">Cancel Order</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Quick Cancel Button for Pending Orders -->
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <form method="POST" action="update_order_status.php" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="btn btn-danger btn-sm ms-2">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sales Reports Tab -->
                <div class="tab-pane fade" id="sales" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Daily Sales by Employee</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Date</th>
                                        <th>Sales Count</th>
                                        <th>Total Kg Sold</th>
                                        <th>Total Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dailySales as $sale): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($sale['username']) ?></td>
                                            <td><?= $sale['sale_day'] ?></td>
                                            <td><?= $sale['sales_count'] ?></td>
                                            <td><?= number_format($sale['total_kg'], 2) ?> kg</td>
                                            <td>D<?= number_format($sale['total_sales'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Location Management Tab -->
                <div class="tab-pane fade" id="locations" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Location Inventory Assignment</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <form method="POST" action="update_location_inventory.php">
                                        <div class="mb-3">
                                            <label for="locationSelect" class="form-label">Select Location</label>
                                            <select class="form-select" id="locationSelect" name="location_id" required>
                                                <?php foreach ($locations as $location): ?>
                                                    <option value="<?= $location['id'] ?>"><?= htmlspecialchars($location['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="employeeSelect" class="form-label">Select Employee</label>
                                            <select class="form-select" id="employeeSelect" name="employee_id" required>
                                                <?php foreach ($employees as $employee): ?>
                                                    <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['username']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php foreach ($fishTypes as $fish): ?>
                                            <div class="mb-3">
                                                <label for="fish_<?= $fish['id'] ?>" class="form-label"><?= htmlspecialchars($fish['name']) ?> (kg)</label>
                                                <input type="number" step="0.1" min="0" class="form-control"
                                                    id="fish_<?= $fish['id'] ?>" name="fish[<?= $fish['id'] ?>]"
                                                    placeholder="Enter quantity">
                                            </div>
                                        <?php endforeach; ?>
                                        <button type="submit" class="btn btn-primary">Assign Inventory</button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <h5>Current Assignments</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Location</th>
                                                    <th>Employee</th>
                                                    <th>Fish Type</th>
                                                    <th>Quantity (kg)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assignments as $assignment): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($assignment['location_name']) ?></td>
                                                        <td><?= htmlspecialchars($assignment['username']) ?></td>
                                                        <td><?= htmlspecialchars($assignment['fish_name']) ?></td>
                                                        <td><?= number_format($assignment['quantity'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fatality Reports Tab -->
                <div class="tab-pane fade" id="fatality" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Fish Fatality Report</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Fish Type</th>
                                        <th>Total Fingerlings</th>
                                        <th>Total Fatalities</th>
                                        <th>Live Count</th>
                                        <th>Mortality Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fatalitySummary as $fish): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($fish['name']) ?></td>
                                            <td><?= $fish['total_fingerlings'] ?></td>
                                            <td><?= $fish['total_fatalities'] ?></td>
                                            <td><?= $fish['live_count'] ?></td>
                                            <td><?= $fish['total_fingerlings'] > 0 ?
                                                    number_format(($fish['total_fatalities'] / $fish['total_fingerlings']) * 100, 2) . '%' : 'N/A' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Inventory Modal -->
        <div class="modal fade" id="updateInventoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Inventory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="update_inventory.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" id="inventoryFishId" name="fish_type_id">
                            <div class="mb-3">
                                <label for="inventoryFishName" class="form-label">Fish Type</label>
                                <input type="text" class="form-control" id="inventoryFishName" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="inventoryQuantity" class="form-label">Quantity (kg)</label>
                                <input type="number" step="0.1" min="0" class="form-control" id="inventoryQuantity" name="quantity" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Fish Type Modal -->
        <div class="modal fade" id="editFishModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Fish Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="update_fish_type.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" id="editFishId" name="id">
                            <div class="mb-3">
                                <label for="editFishName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="editFishName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="editFishDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editFishDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editFishPrice" class="form-label">Price per kg</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="editFishPrice" name="price" required>
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

        <!-- Add Fish Type Modal -->
        <div class="modal fade" id="addFishModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Fish Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="add_fish_type.php" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="addFishName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="addFishName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="addFishDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="addFishDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="addFishPrice" class="form-label">Price per kg</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="addFishPrice" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label for="addFishInitialQuantity" class="form-label">Initial Quantity (kg)</label>
                                <input type="number" step="0.1" min="0" class="form-control" id="addFishInitialQuantity" name="initial_quantity" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Fish Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Details Modal -->
        <div class="modal fade" id="orderDetailsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="orderDetailsContent">
                        Loading...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Initialize all modals
            document.getElementById('updateInventoryModal').addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var fishId = button.getAttribute('data-id');
                var fishName = button.getAttribute('data-name');
                var quantity = button.getAttribute('data-quantity');

                var modal = this;
                modal.querySelector('#inventoryFishId').value = fishId;
                modal.querySelector('#inventoryFishName').value = fishName;
                modal.querySelector('#inventoryQuantity').value = quantity;
            });

            document.getElementById('editFishModal').addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var fishId = button.getAttribute('data-id');
                var fishName = button.getAttribute('data-name');
                var description = button.getAttribute('data-description');
                var price = button.getAttribute('data-price');

                var modal = this;
                modal.querySelector('#editFishId').value = fishId;
                modal.querySelector('#editFishName').value = fishName;
                modal.querySelector('#editFishDescription').value = description;
                modal.querySelector('#editFishPrice').value = price;
            });

            document.getElementById('orderDetailsModal').addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var orderId = button.getAttribute('data-id');

                var modal = this;
                var content = modal.querySelector('#orderDetailsContent');
                content.innerHTML = 'Loading...';

                // Fetch order details via AJAX
                fetch('get_order_details.php?order_id=' + orderId)
                    .then(response => response.text())
                    .then(data => {
                        content.innerHTML = data;
                    })
                    .catch(error => {
                        content.innerHTML = 'Error loading order details.';
                    });
            });

            // Tab functionality
            const triggerTabList = [].slice.call(document.querySelectorAll('#adminTabs button'));
            triggerTabList.forEach(function(triggerEl) {
                const tabTrigger = new bootstrap.Tab(triggerEl);
                triggerEl.addEventListener('click', function(event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });
        </script>
</body>

</html>