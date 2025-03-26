<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['fish_id'])) {
    header("Location: index.php");
    exit();
}

$fishId = $_GET['fish_id'];
$fish = getFishTypeById($fishId);
if (!$fish) {
    header("Location: index.php");
    exit();
}

// Get available quantity
$inventory = getInventory();
$available = 0;
foreach ($inventory as $item) {
    if ($item['fish_type_id'] == $fishId) {
        $available = $item['quantity_kg'];
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = floatval($_POST['quantity']);

    if ($quantity > 0 && $quantity <= $available) {
        $orderId = placeOrder($_SESSION['user_id'], [
            ['fish_type_id' => $fishId, 'quantity' => $quantity]
        ]);

        if ($orderId) {
            $_SESSION['message'] = "Order placed successfully! Order ID: #$orderId";
            header("Location: orders.php");
            exit();
        } else {
            $error = "Failed to place order. Please try again.";
        }
    } else {
        $error = "Invalid quantity. Please enter a value between 0.1 and $available";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Fish Inventory Management</title>
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
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Admin</a>
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
        <h2>Place Order for <?= htmlspecialchars($fish['name']) ?></h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="fishName" class="form-label">Fish Type</label>
                        <input type="text" class="form-control" id="fishName" value="<?= htmlspecialchars($fish['name']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price per kg</label>
                        <input type="text" class="form-control" id="price" value="D<?= number_format($fish['price_per_kg'], 2) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="available" class="form-label">Available Quantity (kg)</label>
                        <input type="text" class="form-control" id="available" value="<?= number_format($available, 2) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity (kg)</label>
                        <input type="number" step="0.1" min="0.1" max="<?= $available ?>" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Place Order</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>