<?php
require_once 'auth.php';
require_once 'functions.php';

// Check if user is employee with process orders permission or admin
if (!canEmployeeProcessOrders() && !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to process orders";
    header("Location: dashboard.php");
    exit();
}

// Get pending orders (show all pending orders for admin, only assigned ones for employees)
if (isAdmin()) {
    $orders = $pdo->query("
        SELECT o.*, u.username 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'pending'
        ORDER BY o.order_date
    ")->fetchAll();
} else {
    // For employees, you might want to show only orders assigned to them
    // Or all pending orders if they can process any
    $orders = $pdo->query("
        SELECT o.*, u.username 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'pending'
        ORDER BY o.order_date
    ")->fetchAll();
}

// Process order status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];
    $action = $_POST['action'];

    $validStatuses = ['processing', 'completed', 'cancelled'];
    if (in_array($action, $validStatuses)) {
        try {
            // For cancellations, we need to revert inventory
            if ($action === 'cancelled') {
                // Get order items first
                $items = getOrderItems($orderId);

                $pdo->beginTransaction();

                // Revert inventory for each item
                foreach ($items as $item) {
                    $stmt = $pdo->prepare("UPDATE inventory SET quantity_kg = quantity_kg + ? WHERE fish_type_id = ?");
                    $stmt->execute([$item['quantity_kg'], $item['fish_type_id']]);
                }

                // Update order status
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', total_amount = 0 WHERE id = ?");
                $stmt->execute([$orderId]);

                $pdo->commit();

                $_SESSION['message'] = "Order #$orderId has been cancelled and inventory reverted";
            } else {
                // For other status changes (processing, completed)
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$action, $orderId]);

                $_SESSION['message'] = "Order #$orderId status updated to " . ucfirst($action);
            }

            header("Location: process_orders.php");
            exit();
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "Error updating order: " . $e->getMessage();
        }
    }
}

include 'navbar.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Orders - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Process Customer Orders</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">No pending orders to process.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order):
                            $items = getOrderItems($order['id']);
                        ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                <td><?= htmlspecialchars($order['username']) ?></td>
                                <td>D<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <ul>
                                        <?php foreach ($items as $item): ?>
                                            <li>
                                                <?= htmlspecialchars($item['name']) ?> -
                                                <?= number_format($item['quantity_kg'], 2) ?> kg
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?= $order['status'] === 'completed' ? 'bg-success' : ($order['status'] === 'processing' ? 'bg-primary' : ($order['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning')) ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <button type="submit" name="action" value="processing"
                                                class="btn btn-sm btn-primary">Process</button>
                                        <?php endif; ?>
                                        <?php if ($order['status'] === 'processing'): ?>
                                            <button type="submit" name="action" value="completed"
                                                class="btn btn-sm btn-success">Complete</button>
                                        <?php endif; ?>
                                        <button type="submit" name="action" value="cancelled"
                                            class="btn btn-sm btn-danger">Cancel</button>
                                    </form>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                        data-bs-target="#orderDetailsModal"
                                        data-orderid="<?= $order['id'] ?>">
                                        Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details #<span id="modalOrderId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    Loading order details...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load order details via AJAX
        document.getElementById('orderDetailsModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-orderid');
            const modal = this;

            modal.querySelector('#modalOrderId').textContent = orderId;

            // Fetch order details
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    modal.querySelector('#orderDetailsContent').innerHTML = data;
                })
                .catch(error => {
                    modal.querySelector('#orderDetailsContent').innerHTML =
                        '<div class="alert alert-danger">Error loading order details</div>';
                });
        });
    </script>
</body>

</html>