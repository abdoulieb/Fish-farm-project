<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['fish_id'])) {
    header("Location: dashboard.php");
    exit();
}

$fishId = $_GET['fish_id'];
$fish = getFishTypeById($fishId);
if (!$fish) {
    header("Location: dashboard.php");
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
                        <a class="nav-link" href="dashboard.php">Home</a>
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
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceTypeSelect = document.getElementById('priceType');
            const priceInput = document.getElementById('price');

            const retailPrice = <?= $fish['price_per_kg'] ?>;
            const wholesalePrice = <?= $fish['wholesale_price_per_kg'] ?? $fish['price_per_kg'] ?>;

            priceTypeSelect.addEventListener('change', function() {
                if (priceTypeSelect.value === 'wholesale') {
                    priceInput.value = `D${wholesalePrice.toFixed(2)}`;
                } else {
                    priceInput.value = `D${retailPrice.toFixed(2)}`;
                }
            });
        });
    </script>
    <!-- Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkoutModalLabel">Confirm Your Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Fish Type:</strong> <span id="modalFishName"></span></p>
                    <p><strong>Price per kg:</strong> <span id="modalPrice"></span></p>
                    <p><strong>Quantity (kg):</strong> <span id="modalQuantity"></span></p>
                    <p><strong>Total Price:</strong> <span id="modalTotalPrice"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmOrderButton">Confirm Order</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const quantityInput = document.getElementById('quantity');
            const modalFishName = document.getElementById('modalFishName');
            const modalPrice = document.getElementById('modalPrice');
            const modalQuantity = document.getElementById('modalQuantity');
            const modalTotalPrice = document.getElementById('modalTotalPrice');
            const confirmOrderButton = document.getElementById('confirmOrderButton');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const fishName = document.getElementById('fishName').value;
                const pricePerKg = parseFloat(document.getElementById('price').value.replace('D', ''));
                const quantity = parseFloat(quantityInput.value);
                const totalPrice = (pricePerKg * quantity).toFixed(2);

                modalFishName.textContent = fishName;
                modalPrice.textContent = `D${pricePerKg.toFixed(2)}`;
                modalQuantity.textContent = `${quantity.toFixed(2)} kg`;
                modalTotalPrice.textContent = `D${totalPrice}`;

                const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
                checkoutModal.show();
            });

            confirmOrderButton.addEventListener('click', function() {
                form.submit();
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>