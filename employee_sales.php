<?php
require_once 'auth.php';
require_once 'functions.php';

// Check if user is employee with sell permission
if (!canEmployeeSell() && !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access the sales page";
    header("Location: index.php");
    exit();
}

// Rest of your existing code...

$fishTypes = getAllFishTypes();
$inventory = getInventory();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['customer_name'] ?? 'Walk-in Customer';
    $customerPhone = $_POST['customer_phone'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'cash';

    $items = [];
    $totalAmount = 0;

    foreach ($_POST['fish_type_id'] as $index => $fishTypeId) {
        $quantity = floatval($_POST['quantity'][$index]);
        if ($quantity > 0) {
            $fish = getFishTypeById($fishTypeId);
            $items[] = [
                'fish_type_id' => $fishTypeId,
                'quantity' => $quantity,
                'unit_price' => $fish['price_per_kg']
            ];
            $totalAmount += $quantity * $fish['price_per_kg'];
        }
    }

    if (!empty($items)) {
        try {
            $pdo->beginTransaction();

            // Create sale record
            $stmt = $pdo->prepare("INSERT INTO sales (employee_id, customer_name, customer_phone, total_amount, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $customerName, $customerPhone, $totalAmount, $paymentMethod]);
            $saleId = $pdo->lastInsertId();

            // Add sale items and update inventory
            foreach ($items as $item) {
                $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, fish_type_id, quantity_kg, unit_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$saleId, $item['fish_type_id'], $item['quantity'], $item['unit_price']]);

                $stmt = $pdo->prepare("UPDATE inventory SET quantity_kg = quantity_kg - ? WHERE fish_type_id = ?");
                $stmt->execute([$item['quantity'], $item['fish_type_id']]);
            }

            $pdo->commit();
            $_SESSION['message'] = "Sale recorded successfully! Total: D" . number_format($totalAmount, 2);
            header("Location: employee_sales.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to record sale: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "No items selected for sale";
    }
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Sales - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .fish-item {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .total-display {
            font-size: 1.5rem;
            font-weight: bold;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Record New Sale</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <form method="POST" id="salesForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="customer_name" class="form-label">Customer Name</label>
                    <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Walk-in Customer">
                </div>
                <div class="col-md-6">
                    <label for="customer_phone" class="form-label">Customer Phone</label>
                    <input type="text" class="form-control" id="customer_phone" name="customer_phone">
                </div>
            </div>

            <div class="mb-3">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select class="form-select" id="payment_method" name="payment_method">
                    <option value="cash">Cash</option>
                    <option value="credit">Credit</option>
                    <option value="mobile_money">Mobile Money</option>
                </select>
            </div>

            <h4 class="mt-4">Sale Items</h4>
            <div id="itemsContainer">
                <div class="fish-item row">
                    <div class="col-md-4">
                        <label class="form-label">Fish Type</label>
                        <select class="form-select fish-type" name="fish_type_id[]" required>
                            <option value="">Select Fish</option>
                            <?php foreach ($fishTypes as $fish):
                                $available = 0;
                                foreach ($inventory as $item) {
                                    if ($item['fish_type_id'] == $fish['id']) {
                                        $available = $item['quantity_kg'];
                                        break;
                                    }
                                }
                                if ($available > 0): ?>
                                    <option value="<?= $fish['id'] ?>" data-price="<?= $fish['price_per_kg'] ?>" data-available="<?= $available ?>">
                                        <?= htmlspecialchars($fish['name']) ?> (D<?= number_format($fish['price_per_kg'], 2) ?>/kg)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity (kg)</label>
                        <input type="number" step="0.1" min="0.1" class="form-control quantity" name="quantity[]" required>
                        <small class="text-muted available-text">Available: 0 kg</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Price (D)</label>
                        <input type="number" step="0.01" class="form-control price" readonly>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                    </div>
                </div>
            </div>

            <button type="button" id="addItem" class="btn btn-secondary mt-2">Add Another Fish</button>

            <div class="total-display mt-4">
                Total: D<span id="totalAmount">0.00</span>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Complete Sale</button>
                <a href="employee_sales.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add new item row
            document.getElementById('addItem').addEventListener('click', function() {
                const newItem = document.querySelector('.fish-item').cloneNode(true);
                newItem.querySelector('.quantity').value = '';
                newItem.querySelector('.price').value = '';
                newItem.querySelector('.available-text').textContent = 'Available: 0 kg';
                document.getElementById('itemsContainer').appendChild(newItem);
                updateRemoveButtons();
            });

            // Update remove buttons
            function updateRemoveButtons() {
                document.querySelectorAll('.remove-item').forEach(button => {
                    button.addEventListener('click', function() {
                        if (document.querySelectorAll('.fish-item').length > 1) {
                            this.closest('.fish-item').remove();
                            calculateTotal();
                        }
                    });
                });
            }

            // Update price and available quantity when fish type changes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('fish-type')) {
                    const selectedOption = e.target.options[e.target.selectedIndex];
                    const price = selectedOption.dataset.price || '0';
                    const available = selectedOption.dataset.available || '0';

                    const itemRow = e.target.closest('.fish-item');
                    itemRow.querySelector('.price').value = price;
                    itemRow.querySelector('.available-text').textContent = `Available: ${available} kg`;
                    itemRow.querySelector('.quantity').max = available;

                    calculateTotal();
                }
            });

            // Calculate total when quantity changes
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity')) {
                    calculateTotal();
                }
            });

            // Calculate total amount
            function calculateTotal() {
                let total = 0;
                document.querySelectorAll('.fish-item').forEach(item => {
                    const quantity = parseFloat(item.querySelector('.quantity').value) || 0;
                    const price = parseFloat(item.querySelector('.price').value) || 0;
                    total += quantity * price;
                });
                document.getElementById('totalAmount').textContent = total.toFixed(2);
            }

            updateRemoveButtons();
        });
    </script>
</body>

</html>