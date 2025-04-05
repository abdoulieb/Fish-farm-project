
<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
    $orderId = intval($_POST['delete_order_id']);
    deleteOrder($orderId); // Replace with the function to delete the order
    $_SESSION['message'] = "Order #$orderId has been deleted.";
    header("Location: orders.php");
    exit();
}

$orders = getOrders($_SESSION['user_id']);
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Fish Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Fish Farm</a>
            <?php if (isEmployee()): ?>
                <a class="navbar-brand" href="employee_sales.php">Make a Sale</a>
            <?php endif; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">My Orders</a>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Admin</a>
                        </li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_employees.php">Manage Employees</a>
                        </li>
                    <?php endif; ?>
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
        <h2>My Orders</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">You have no orders yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                <td>
                                    <?php
                                    $items = getOrderItems($order['id']);
                                    foreach ($items as $item) {
                                        echo htmlspecialchars($item['name']) . ' - ' . $item['quantity_kg'] . ' kg<br>';
                                    }
                                    ?>
                                </td>
                                <td>D<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge 
                                <?= $order['status'] === 'completed' ? 'bg-success' : ($order['status'] === 'processing' ? 'bg-primary' : ($order['status'] === 'cancelled' ? 'bg-danger' : 'bg-secondary')) ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>