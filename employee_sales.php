<?php
require_once 'auth.php';
require_once 'functions.php';

// Check if user has sales permission (employee with sell permission or admin)
if (!canEmployeeSell() && !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access the sales page";
    header("Location: dashboard.php");
    exit();
}

// Get inventory based on user role
if (isAdmin()) {
    // Admins can see all inventory
    $assignedInventory = $pdo->prepare("
        SELECT ft.id, ft.name, ft.price_per_kg, i.quantity_kg as available_kg 
        FROM fish_types ft
        JOIN inventory i ON ft.id = i.fish_type_id
    ");
    $assignedInventory->execute();
} else {
    // Employees see only their assigned inventory
    $assignedInventory = $pdo->prepare("
        SELECT ft.id, ft.name, ft.price_per_kg, li.quantity as available_kg 
        FROM location_inventory li
        JOIN fish_types ft ON li.fish_type_id = ft.id
        WHERE li.employee_id = ?
    ");
    $assignedInventory->execute([$_SESSION['user_id']]);
}
$fishTypes = $assignedInventory->fetchAll();

// Get sales history based on user role
if (isAdmin()) {
    $employeeFilter = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : null;

    $sql = "
        SELECT s.*, COUNT(si.id) as item_count, SUM(si.quantity_kg) as total_kg, u.username as employee_name
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        LEFT JOIN users u ON s.employee_id = u.id
    ";

    if ($employeeFilter) {
        $sql .= " WHERE s.employee_id = :employee_id ";
    }

    $sql .= " GROUP BY s.id ORDER BY s.sale_date DESC";

    $employeeSales = $pdo->prepare($sql);

    if ($employeeFilter) {
        $employeeSales->execute([':employee_id' => $employeeFilter]);
    } else {
        $employeeSales->execute();
    }
} else {
    $employeeSales = $pdo->prepare("
        SELECT s.*, COUNT(si.id) as item_count, SUM(si.quantity_kg) as total_kg
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        WHERE s.employee_id = ?
        GROUP BY s.id
        ORDER BY s.sale_date DESC
    ");
    $employeeSales->execute([$_SESSION['user_id']]);
}
$sales = $employeeSales->fetchAll();

// Get today's sales for daily report
$today = date('Y-m-d');
if (isAdmin()) {
    $dailySales = $pdo->prepare("
        SELECT s.*, u.username as employee_name
        FROM sales s
        LEFT JOIN users u ON s.employee_id = u.id
        WHERE DATE(s.sale_date) = ?
        ORDER BY s.sale_date
    ");
    $dailySales->execute([$today]);
} else {
    $dailySales = $pdo->prepare("
        SELECT s.*
        FROM sales s
        WHERE s.employee_id = ? AND DATE(s.sale_date) = ?
        ORDER BY s.sale_date
    ");
    $dailySales->execute([$_SESSION['user_id'], $today]);
}
$todaySales = $dailySales->fetchAll();

// Calculate daily totals
$totalExpected = 0;
$totalCashSales = 0;
$totalCreditSales = 0;
$totalMobileMoneySales = 0;

foreach ($todaySales as $sale) {
    $totalExpected += $sale['total_amount'];

    if ($sale['payment_method'] === 'cash') {
        $totalCashSales += $sale['total_amount'];
    } elseif ($sale['payment_method'] === 'credit') {
        $totalCreditSales += $sale['total_amount'];
    } elseif ($sale['payment_method'] === 'mobile_money') {
        $totalMobileMoneySales += $sale['total_amount'];
    }
}

// Get reconciliation reports for admin view or employee's own view
if (isAdmin()) {
    $reconciliationReports = $pdo->prepare("
        SELECT cr.*, u.username as employee_name 
        FROM cash_reconciliations cr
        JOIN users u ON cr.employee_id = u.id
        ORDER BY cr.report_date DESC, cr.submitted_at DESC
    ");
    $reconciliationReports->execute();
} else {
    $reconciliationReports = $pdo->prepare("
        SELECT cr.*, u.username as employee_name 
        FROM cash_reconciliations cr
        JOIN users u ON cr.employee_id = u.id
        WHERE cr.employee_id = ? AND cr.status = 'pending'
        ORDER BY cr.report_date DESC, cr.submitted_at DESC
    ");
    $reconciliationReports->execute([$_SESSION['user_id']]);
}
$allReconciliations = $reconciliationReports->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle new sale submission
    if (isset($_POST['fish_type_id'])) {
        $paymentMethod = $_POST['payment_method'] ?? 'cash';

        $items = [];
        $totalAmount = 0;

        // For employees (non-admins), check they have accepted assignments
        if (!isAdmin()) {
            foreach ($_POST['fish_type_id'] as $index => $fishTypeId) {
                $quantity = floatval($_POST['quantity'][$index]);
                if ($quantity > 0) {
                    $stmt = $pdo->prepare("
                        SELECT status FROM location_inventory
                        WHERE employee_id = ? AND fish_type_id = ? AND status = 'accepted'
                    ");
                    $stmt->execute([$_SESSION['user_id'], $fishTypeId]);
                    $assignment = $stmt->fetch();

                    if (!$assignment) {
                        echo "<script>
                            alert('You must have accepted inventory assignments to make sales.');
                            window.location.href = 'employee_sales.php';
                        </script>";
                        exit();
                    }
                }

                $fish = getFishTypeById($fishTypeId);
                $items[] = [
                    'fish_type_id' => $fishTypeId,
                    'quantity' => $quantity,
                    'unit_price' => $fish['price_per_kg']
                ];
                $totalAmount += $quantity * $fish['price_per_kg'];
            }
        } else {
            // For admins, check main inventory availability
            foreach ($_POST['fish_type_id'] as $index => $fishTypeId) {
                $quantity = floatval($_POST['quantity'][$index]);
                if ($quantity > 0) {
                    $stmt = $pdo->prepare("
                        SELECT quantity_kg FROM inventory 
                        WHERE fish_type_id = ?
                    ");
                    $stmt->execute([$fishTypeId]);
                    $inventory = $stmt->fetch();

                    if (!$inventory || $inventory['quantity_kg'] < $quantity) {
                        $fishName = getFishTypeById($fishTypeId)['name'];
                        $_SESSION['error'] = "Not enough inventory available for $fishName";
                        header("Location: employee_sales.php");
                        exit();
                    }
                }

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

                // First validate all inventory levels
                foreach ($items as $item) {
                    if ($item['quantity'] <= 0) continue;

                    // For employees, check their assigned inventory
                    if (!isAdmin()) {
                        $stmt = $pdo->prepare("
                            SELECT quantity FROM location_inventory 
                            WHERE employee_id = ? AND fish_type_id = ? AND status = 'accepted'
                            FOR UPDATE
                        ");
                        $stmt->execute([$_SESSION['user_id'], $item['fish_type_id']]);
                        $assignment = $stmt->fetch();

                        if (!$assignment || $assignment['quantity'] < $item['quantity']) {
                            throw new Exception("Not enough inventory available for fish type ID: " . $item['fish_type_id']);
                        }
                    }

                    // For all sales, check main inventory
                    $stmt = $pdo->prepare("
                        SELECT quantity_kg FROM inventory 
                        WHERE fish_type_id = ?
                        FOR UPDATE
                    ");
                    $stmt->execute([$item['fish_type_id']]);
                    $inventory = $stmt->fetch();

                    if (!$inventory || $inventory['quantity_kg'] < $item['quantity']) {
                        throw new Exception("Not enough inventory available in main stock for fish type ID: " . $item['fish_type_id']);
                    }
                }

                // Create sale record
                $stmt = $pdo->prepare("
                    INSERT INTO sales (employee_id, total_amount, payment_method) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $totalAmount, $paymentMethod]);
                $saleId = $pdo->lastInsertId();

                // Add sale items and update inventories
                foreach ($items as $item) {
                    if ($item['quantity'] <= 0) continue;

                    // Add sale item
                    $stmt = $pdo->prepare("
                        INSERT INTO sale_items (sale_id, fish_type_id, quantity_kg, unit_price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$saleId, $item['fish_type_id'], $item['quantity'], $item['unit_price']]);

                    // Update main inventory (for both admin and employee sales)
                    $stmt = $pdo->prepare("
                        UPDATE inventory SET quantity_kg = quantity_kg - ? 
                        WHERE fish_type_id = ?
                    ");
                    $stmt->execute([$item['quantity'], $item['fish_type_id']]);

                    // Update employee's assigned inventory (employees only)
                    if (!isAdmin()) {
                        $stmt = $pdo->prepare("
                            UPDATE location_inventory SET quantity = quantity - ? 
                            WHERE employee_id = ? AND fish_type_id = ?
                        ");
                        $stmt->execute([$item['quantity'], $_SESSION['user_id'], $item['fish_type_id']]);
                    }
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
    // Handle daily report submission
    // Handle daily report submission
    elseif (isset($_POST['submit_daily_report'])) {
        $pettyCash = floatval($_POST['petty_cash'] ?? 0);
        $physicalCash = floatval($_POST['physical_cash'] ?? 0);

        // Calculate expected cash as petty cash + total cash sales
        $expectedCash = $pettyCash + $totalCashSales;
        $deficit = $expectedCash - $physicalCash;
        $totalCash = $physicalCash + $pettyCash;

        // Save the reconciliation
        try {
            $stmt = $pdo->prepare("
            INSERT INTO cash_reconciliations 
            (employee_id, report_date, expected_amount, physical_cash, petty_cash, deficit, total_cash)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
            $stmt->execute([
                $_SESSION['user_id'],
                $today,
                $expectedCash,  // Now includes petty cash
                $physicalCash,
                $pettyCash,
                $deficit,
                $totalCash
            ]);

            $_SESSION['message'] = "Daily sales report submitted successfully!";
            header("Location: employee_sales.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error saving report: " . $e->getMessage();
        }
    }
}

// Handle sale cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_sale'])) {
    $saleId = intval($_POST['sale_id']);
    if (cancelSale($saleId, $_SESSION['user_id'])) {
        $_SESSION['message'] = "Sale #$saleId has been cancelled successfully";
        header("Location: employee_sales.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to cancel sale #$saleId";
    }
}

// Check if reconciliation was already submitted today
$hasSubmittedToday = false;
if (!isAdmin()) {
    $checkSubmission = $pdo->prepare("
        SELECT id FROM cash_reconciliations 
        WHERE employee_id = ? AND DATE(report_date) = ?
    ");
    $checkSubmission->execute([$_SESSION['user_id'], $today]);
    $hasSubmittedToday = (bool)$checkSubmission->fetch();
}

// Handle reconciliation updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reconciliation'])) {
    $reportId = intval($_POST['report_id']);
    $physicalCash = floatval($_POST['physical_cash']);
    $pettyCash = floatval($_POST['petty_cash']);

    // Verify the report belongs to the employee (if not admin)
    if (!isAdmin()) {
        $checkReport = $pdo->prepare("SELECT id FROM cash_reconciliations WHERE id = ? AND employee_id = ? AND status = 'pending'");
        $checkReport->execute([$reportId, $_SESSION['user_id']]);
        if (!$checkReport->fetch()) {
            $_SESSION['error'] = "You can only edit your own pending reconciliations.";
            header("Location: employee_sales.php");
            exit();
        }
    }

    if (updateReconciliation($reportId, $physicalCash, $pettyCash)) {
        $_SESSION['message'] = "Reconciliation updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update reconciliation.";
    }

    header("Location: employee_sales.php");
    exit();
}

// Handle approval by admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_reconciliation'])) {
    if (!isAdmin()) {
        $_SESSION['error'] = "Only admins can approve reconciliations.";
        header("Location: employee_sales.php");
        exit();
    }

    $reportId = intval($_POST['report_id']);

    try {
        $stmt = $pdo->prepare("UPDATE cash_reconciliations SET status = 'approved' WHERE id = ?");
        $stmt->execute([$reportId]);

        $_SESSION['message'] = "Reconciliation approved successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error approving reconciliation: " . $e->getMessage();
    }

    header("Location: employee_sales.php");
    exit();
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

        .sales-table {
            margin-top: 30px;
        }

        .tab-content {
            padding: 20px 0;
        }

        .payment-method-badge {
            font-size: 0.8em;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .cash-badge {
            background-color: #d4edda;
            color: #155724;
        }

        .credit-badge {
            background-color: #fff3cd;
            color: #856404;
        }

        .mobile-badge {
            background-color: #cce5ff;
            color: #004085;
        }

        .summary-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .discrepancy-badge {
            background-color: #f8d7da;
            color: #721c24;
        }

        #completeSaleBtn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .is-invalid {
            border-color: #dc3545;
            background-color: #fff3f3;
        }

        .is-invalid+.available-text {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2><?= isAdmin() ? 'Admin' : 'Employee' ?> Sales Dashboard</h2>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="new-sale-tab" data-bs-toggle="tab" data-bs-target="#new-sale" type="button" role="tab">New Sale</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales History</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="daily-report-tab" data-bs-toggle="tab" data-bs-target="#daily-report" type="button" role="tab">Daily Report</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reconciliation-tab" data-bs-toggle="tab" data-bs-target="#reconciliation" type="button" role="tab">My Reconciliations</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="employeeTabsContent">
            <!-- New Sale Tab -->
            <div class="tab-pane fade show active" id="new-sale" role="tabpanel">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <!-- Button to trigger modal -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#salesModal">
                    Add New Sale
                </button>
                <div class="mt-4">
                    <h5>Available Inventory</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Fish Type</th>
                                <th>Price per Kg (D)</th>
                                <th>Available Kg</th>
                                <th>Total Value (D)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fishTypes as $fish): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fish['name']) ?></td>
                                    <td>D<?= number_format($fish['price_per_kg'], 2) ?></td>
                                    <td>
                                        <strong>
                                            <span style="font-size: 1.2rem; color: <?= $fish['available_kg'] > 10 ? 'green' : ($fish['available_kg'] > 5 ? 'orange' : 'red') ?>;">
                                                <?= number_format($fish['available_kg'], 2) ?> kg
                                            </span>
                                        </strong>
                                    </td>
                                    <td><strong>D<?= number_format($fish['price_per_kg'] * $fish['available_kg'], 2) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Modal -->
                <div class="modal fade" id="salesModal" tabindex="-1" aria-labelledby="salesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="salesModalLabel">New Sale</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" id="salesForm">
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
                                                    <?php foreach ($fishTypes as $fish): ?>
                                                        <option value="<?= $fish['id'] ?>"
                                                            data-price="<?= $fish['price_per_kg'] ?>"
                                                            data-available="<?= $fish['available_kg'] ?>">
                                                            🐟 <?= htmlspecialchars($fish['name']) ?> (D<?= number_format($fish['price_per_kg'], 2) ?>/kg)
                                                        </option>
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
                                        <label for="money_given" class="form-label">Money Given by Customer (D)</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="money_given" name="money_given" required>
                                        <div class="mt-2">
                                            <span id="changeDisplay" class="badge" style="font-size: 1.2rem; font-weight: bold; background-color: rgba(114, 101, 101, 0.8); color: white;">Change: D0.00</span>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" id="completeSaleBtn" class="btn btn-primary" disabled>Complete Sale</button>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const moneyGivenInput = document.getElementById('money_given');
                        const totalAmountDisplay = document.getElementById('totalAmount');
                        const changeDisplay = document.getElementById('changeDisplay');

                        function updateChangeDisplay() {
                            const totalAmount = parseFloat(document.getElementById('totalAmount').textContent) || 0;
                            const moneyGiven = parseFloat(document.getElementById('money_given').value) || 0;
                            const change = moneyGiven - totalAmount;
                            const changeDisplay = document.getElementById('changeDisplay');

                            if (moneyGiven === 0) {
                                changeDisplay.textContent = 'Enter amount given';
                                changeDisplay.className = 'badge bg-light text-dark';
                            } else if (change < 0) {
                                changeDisplay.textContent = `Short: D${Math.abs(change).toFixed(2)}`;
                                changeDisplay.className = 'badge bg-light text-danger';
                            } else if (change === 0) {
                                changeDisplay.textContent = 'Exact amount';
                                changeDisplay.className = 'badge bg-light text-success';
                            } else {
                                changeDisplay.textContent = `Change: D${change.toFixed(2)}`;
                                changeDisplay.className = 'badge bg-light text-success';
                            }
                        }

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

                                // Set max attribute to prevent entering more than available
                                const quantityInput = itemRow.querySelector('.quantity');
                                quantityInput.max = available;
                                if (parseFloat(quantityInput.value) > parseFloat(available)) {
                                    quantityInput.value = available;
                                }

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
                            updateChangeDisplay();
                            updateCompleteButton();
                        }

                        // Update complete button state
                        function updateCompleteButton() {
                            const totalAmount = parseFloat(document.getElementById('totalAmount').textContent) || 0;
                            const moneyGiven = parseFloat(document.getElementById('money_given').value) || 0;
                            const paymentMethod = document.getElementById('payment_method').value;
                            const completeBtn = document.getElementById('completeSaleBtn');

                            // For credit sales, always enable the button
                            if (paymentMethod === 'credit') {
                                completeBtn.disabled = false;
                                return;
                            }

                            // For other payment methods, enable only if money given >= total
                            completeBtn.disabled = moneyGiven < totalAmount;
                        }

                        // Call this function when money given or payment method changes
                        document.getElementById('money_given').addEventListener('input', updateCompleteButton);
                        document.getElementById('payment_method').addEventListener('change', updateCompleteButton);

                        // Form submission validation
                        document.getElementById('salesForm').addEventListener('submit', function(e) {
                            // Check all quantities don't exceed available amounts
                            let isValid = true;
                            document.querySelectorAll('.fish-item').forEach(item => {
                                const fishSelect = item.querySelector('.fish-type');
                                const quantityInput = item.querySelector('.quantity');

                                if (fishSelect.value && quantityInput.value) {
                                    const available = parseFloat(fishSelect.options[fishSelect.selectedIndex].dataset.available || 0);
                                    const quantity = parseFloat(quantityInput.value) || 0;

                                    if (quantity > available) {
                                        isValid = false;
                                        quantityInput.classList.add('is-invalid');
                                        const fishName = fishSelect.options[fishSelect.selectedIndex].text.split(' (')[0];
                                        alert(`Cannot sell ${quantity}kg of ${fishName} - only ${available}kg available`);
                                    } else {
                                        quantityInput.classList.remove('is-invalid');
                                    }
                                }
                            });

                            if (!isValid) {
                                e.preventDefault();
                                return false;
                            }

                            const totalAmount = parseFloat(document.getElementById('totalAmount').textContent) || 0;
                            const moneyGiven = parseFloat(document.getElementById('money_given').value) || 0;
                            const paymentMethod = document.getElementById('payment_method').value;
                            const change = moneyGiven - totalAmount;

                            // Skip validation for credit sales
                            if (paymentMethod === 'credit') {
                                return true;
                            }

                            // For other payment methods, validate money given
                            if (change < 0) {
                                e.preventDefault();
                                alert(`Insufficient amount given!\n\nTotal: D${totalAmount.toFixed(2)}\nGiven: D${moneyGiven.toFixed(2)}\nShort: D${Math.abs(change).toFixed(2)}`);
                                document.getElementById('money_given').focus();
                                return false;
                            }

                            // If change is not exactly 0, show confirmation
                            if (change !== 0) {
                                if (!confirm(`Confirm transaction:\n\nTotal: D${totalAmount.toFixed(2)}\nGiven: D${moneyGiven.toFixed(2)}\nChange: D${change.toFixed(2)}`)) {
                                    e.preventDefault();
                                    return false;
                                }
                            }
                        });

                        // Initialize change display
                        document.getElementById('money_given').addEventListener('input', updateChangeDisplay);
                        updateChangeDisplay();
                        updateRemoveButtons();
                    });
                </script>
            </div>

            <!-- Sales History Tab -->
            <div class="tab-pane fade" id="sales" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Sales History</h4>
                    <?php if (isAdmin()): ?>
                        <div class="col-md-4">
                            <select class="form-select" id="employeeFilter">
                                <option value="">All Employees</option>
                                <?php
                                $allEmployees = $pdo->query("SELECT id, username FROM users WHERE role = 'employee' ORDER BY username")->fetchAll();
                                foreach ($allEmployees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= isset($_GET['employee_id']) && $_GET['employee_id'] == $emp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($sales)): ?>
                    <div class="alert alert-info">No sales recorded yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped sales-table">
                            <thead>
                                <tr>
                                    <th>Sale ID</th>
                                    <?php if (isAdmin()): ?>
                                        <th>Employee</th>
                                    <?php endif; ?>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total Kg</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td>#<?= $sale['id'] ?></td>
                                        <?php if (isAdmin()): ?>
                                            <td><?= htmlspecialchars($sale['employee_name'] ?? 'Admin') ?></td>
                                        <?php endif; ?>
                                        <td><?= date('M d, Y H:i', strtotime($sale['sale_date'])) ?></td>
                                        <td><?= $sale['item_count'] ?></td>
                                        <td><?= number_format($sale['total_kg'], 2) ?></td>
                                        <td>D<?= number_format($sale['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="payment-method-badge 
                                    <?= $sale['payment_method'] === 'cash' ? 'cash-badge' : ($sale['payment_method'] === 'credit' ? 'credit-badge' : 'mobile-badge') ?>">
                                                <?= ucfirst(str_replace('_', ' ', $sale['payment_method'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Daily Report Tab -->
            <div class="tab-pane fade" id="daily-report" role="tabpanel">
                <h4>Daily Sales Report - <?= date('F j, Y') ?></h4>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <div class="row mt-3">
                    <div class="col-md-8">
                        <div class="summary-card">
                            <h5>Today's Sales</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sale ID</th>
                                        <?php if (isAdmin()): ?>
                                            <th>Employee</th>
                                        <?php endif; ?>
                                        <th>Time</th>
                                        <th>Payment Method</th>
                                        <th>Amount (D)</th>
                                        <th>Kg Sold</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalKgSold = 0;
                                    foreach ($todaySales as $sale):
                                        // Get kg sold for this sale
                                        $stmt = $pdo->prepare("SELECT SUM(quantity_kg) as total_kg FROM sale_items WHERE sale_id = ?");
                                        $stmt->execute([$sale['id']]);
                                        $kg = $stmt->fetch()['total_kg'] ?? 0;
                                        $totalKgSold += $kg;
                                    ?>
                                        <tr>
                                            <td>#<?= $sale['id'] ?></td>
                                            <?php if (isAdmin()): ?>
                                                <td><?= htmlspecialchars($sale['employee_name'] ?? 'Admin') ?></td>
                                            <?php endif; ?>
                                            <td><?= date('H:i', strtotime($sale['sale_date'])) ?></td>
                                            <td>
                                                <span class="payment-method-badge 
                                        <?= $sale['payment_method'] === 'cash' ? 'cash-badge' : ($sale['payment_method'] === 'credit' ? 'credit-badge' : 'mobile-badge') ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $sale['payment_method'])) ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($sale['total_amount'], 2) ?></td>
                                            <td><?= number_format($kg, 2) ?></td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                                                    <button type="submit" name="cancel_sale" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to cancel this sale?')">
                                                        Cancel
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-primary">
                                        <td colspan="<?= isAdmin() ? 4 : 3 ?>" class="text-end"><strong>Total All Sales:</strong></td>
                                        <td class="fw-bold">D<?= number_format($totalExpected, 2) ?></td>
                                        <td class="fw-bold"><?= number_format($totalKgSold, 2) ?> kg</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalCashSales > 0 && !isAdmin() && !$hasSubmittedToday): ?>
                            <form method="POST">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5>Cash Reconciliation</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Physical Cash Count (D)</label>
                                                <input type="number" step="0.01" class="form-control" name="physical_cash" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Petty Cash (D)</label>
                                                <input type="number" step="0.01" class="form-control" name="petty_cash" value="0">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Expected Cash:</strong> D<?= number_format($totalCashSales, 2) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Deficit/Surplus:</strong> D<span id="deficitDisplay">0.00</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="submit_daily_report" class="btn btn-primary">Submit Daily Report</button>
                            </form>
                        <?php elseif ($hasSubmittedToday): ?>
                            <div class="alert alert-info">You have already submitted your reconciliation report for today.</div>
                        <?php elseif (isAdmin()): ?>
                            <div class="alert alert-info">Admins can view but not submit cash reconciliations.</div>
                        <?php else: ?>
                            <div class="alert alert-info">No cash sales today. Reconciliation not needed.</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <div class="summary-card bg-light">
                            <h5>Today's Summary</h5>
                            <table class="table">
                                <tr>
                                    <th>Total Sales:</th>
                                    <td>D<?= number_format($totalExpected, 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Total Kg Sold:</th>
                                    <td><?= number_format($totalKgSold, 2) ?> kg</td>
                                </tr>
                                <tr>
                                    <th>Cash Sales:</th>
                                    <td>D<?= number_format($totalCashSales, 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Credit Sales:</th>
                                    <td>D<?= number_format($totalCreditSales, 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Mobile Money:</th>
                                    <td>D<?= number_format($totalMobileMoneySales, 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reconciliation Reports Tab -->
            <div class="tab-pane fade" id="reconciliation" role="tabpanel">
                <h4><?= isAdmin() ? 'All' : 'My' ?> Cash Reconciliation Reports</h4>

                <?php if (isAdmin()): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Filter Reports</h5>
                        </div>
                        <div class="card-body">
                            <form id="reconciliationFilter">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Employee</label>
                                        <select class="form-select" name="employee_filter">
                                            <option value="">All Employees</option>
                                            <?php
                                            $employees = $pdo->query("SELECT id, username FROM users WHERE role = 'employee'")->fetchAll();
                                            foreach ($employees as $emp): ?>
                                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['username']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status_filter">
                                            <option value="">All Statuses</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date From</label>
                                        <input type="date" class="form-control" name="date_from">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date To</label>
                                        <input type="date" class="form-control" name="date_to">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                    <button type="button" class="btn btn-success" onclick="window.print()">
                                        <i class="bi bi-printer"></i> Print Reports
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <button type="button" class="btn btn-success" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print My Reports
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (empty($allReconciliations)): ?>
                    <div class="alert alert-info">No reconciliation reports found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <?php if (isAdmin()): ?>
                                        <th>Employee</th>
                                    <?php endif; ?>
                                    <th>Date</th>
                                    <th>Expected (D)</th>
                                    <th>Physical Cash (D)</th>
                                    <th>Petty Cash (D)</th>
                                    <th>Total Cash (D)</th>
                                    <th>Deficit/Surplus (D)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allReconciliations as $report):
                                    $deficit = $report['deficit'];
                                    $isProblem = $deficit != 0; // Changed to show red for any discrepancy (positive or negative)
                                ?>
                                    <tr>
                                        <?php if (isAdmin()): ?>
                                            <td><?= htmlspecialchars($report['employee_name']) ?></td>
                                        <?php endif; ?>
                                        <td><?= date('M d, Y H:i', strtotime($report['report_date'])) ?></td>
                                        <td><?= number_format($report['expected_amount'], 2) ?></td>
                                        <td><?= number_format($report['physical_cash'], 2) ?></td>
                                        <td><?= number_format($report['petty_cash'], 2) ?></td>
                                        <td><?= number_format($report['total_cash'], 2) ?></td>
                                        <td class="<?= $isProblem ? 'text-danger fw-bold' : 'text-success' ?>">
                                            <?= number_format($deficit, 2) ?>
                                        </td>
                                        <td>
                                            <?php if ($report['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($report['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-primary edit-reconciliation"
                                                    data-id="<?= $report['id'] ?>"
                                                    data-physical="<?= $report['physical_cash'] ?>"
                                                    data-petty="<?= $report['petty_cash'] ?>"
                                                    data-expected="<?= $report['expected_amount'] ?>">
                                                    Edit
                                                </button>
                                                <?php if (isAdmin()): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                        <button type="submit" name="approve_reconciliation" class="btn btn-sm btn-success">
                                                            Approve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Locked</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Edit Reconciliation Modal -->
                <div class="modal fade" id="editReconciliationModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Reconciliation Report</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" id="editReconciliationForm">
                                <div class="modal-body">
                                    <input type="hidden" name="report_id" id="report_id">
                                    <div class="mb-3">
                                        <label class="form-label">Physical Cash (D)</label>
                                        <input type="number" step="0.01" class="form-control" name="physical_cash" id="edit_physical_cash" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Petty Cash (D)</label>
                                        <input type="number" step="0.01" class="form-control" name="petty_cash" id="edit_petty_cash" required>
                                    </div>
                                    <div class="alert alert-info">
                                        Expected Cash: D<span id="edit_expected_amount">0.00</span>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_reconciliation" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Print Styles -->
                <style media="print">
                    body * {
                        visibility: hidden;
                    }

                    #reconciliation,
                    #reconciliation * {
                        visibility: visible;
                    }

                    #reconciliation {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                    }

                    .no-print {
                        display: none !important;
                    }

                    table {
                        width: 100% !important;
                    }
                </style>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit reconciliation modal
            const editModal = new bootstrap.Modal(document.getElementById('editReconciliationModal'));

            document.querySelectorAll('.edit-reconciliation').forEach(button => {
                button.addEventListener('click', function() {
                    const reportId = this.dataset.id;
                    const physicalCash = this.dataset.physical;
                    const pettyCash = this.dataset.petty;
                    const expectedAmount = this.dataset.expected;

                    // Set form values
                    document.getElementById('report_id').value = reportId;
                    document.getElementById('edit_physical_cash').value = physicalCash;
                    document.getElementById('edit_petty_cash').value = pettyCash;
                    document.getElementById('edit_expected_amount').textContent = parseFloat(expectedAmount).toFixed(2);

                    editModal.show();
                });
            });

            // Filter functionality for reconciliation reports
            const filterForm = document.getElementById('reconciliationFilter');
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const params = new URLSearchParams();

                    for (const [key, value] of formData.entries()) {
                        if (value) params.append(key, value);
                    }

                    window.location.href = `employee_sales.php?${params.toString()}`;
                });
            }

            // Employee filter change
            document.getElementById('employeeFilter')?.addEventListener('change', function() {
                const employeeId = this.value;
                if (employeeId) {
                    window.location.href = `employee_sales.php?employee_id=${employeeId}`;
                } else {
                    window.location.href = 'employee_sales.php';
                }
            });

            // Daily report calculations
            const physicalCashInput = document.querySelector('input[name="physical_cash"]');
            if (physicalCashInput) {
                physicalCashInput.addEventListener('input', updateDailyCalculations);
                document.querySelector('input[name="petty_cash"]').addEventListener('input', updateDailyCalculations);

                function updateDailyCalculations() {
                    const physicalCash = parseFloat(physicalCashInput.value) || 0;
                    const pettyCash = parseFloat(document.querySelector('input[name="petty_cash"]').value) || 0;
                    const cashSalesAmount = <?= $totalCashSales ?>;

                    // Calculate expected cash as petty cash + total cash sales
                    const expectedCash = pettyCash + cashSalesAmount;
                    const deficit = expectedCash - physicalCash;
                    document.getElementById('deficitDisplay').textContent = deficit.toFixed(2);
                }
            }
        });
    </script>
</body>

</html>