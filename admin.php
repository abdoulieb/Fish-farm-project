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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fish Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                                            <li><a class="dropdown-item" href="update_order_status.php?order_id=<?= $order['id'] ?>&status=pending">Pending</a></li>
                                            <li><a class="dropdown-item" href="update_order_status.php?order_id=<?= $order['id'] ?>&status=processing">Processing</a></li>
                                            <li><a class="dropdown-item" href="update_order_status.php?order_id=<?= $order['id'] ?>&status=completed">Completed</a></li>
                                            <li><a class="dropdown-item" href="update_order_status.php?order_id=<?= $order['id'] ?>&status=cancelled">Cancelled</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
        // Update Inventory Modal
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

        // Edit Fish Type Modal
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

        // Order Details Modal
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
    </script>
</body>

</html>