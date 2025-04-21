<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isEmployee() && !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: dashboard.php");
    exit();
}

// Get today's sales for this employee
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT s.id, s.total_amount, s.sale_date, s.payment_method
    FROM sales s
    WHERE s.employee_id = ? AND DATE(s.sale_date) = ?
    ORDER BY s.sale_date ASC
");
$stmt->execute([$_SESSION['user_id'], $today]);
$sales = $stmt->fetchAll();

// Calculate totals
$totalExpected = 0;
$totalCashSales = 0;
$totalCreditSales = 0;
$totalMobileMoneySales = 0;

foreach ($sales as $sale) {
    $totalExpected += $sale['total_amount'];

    if ($sale['payment_method'] == 'cash') {
        $totalCashSales += $sale['total_amount'];
    } elseif ($sale['payment_method'] === 'credit') {
        $totalCreditSales += $sale['total_amount'];
    } elseif ($sale['payment_method'] === 'mobile_money') {
        $totalMobileMoneySales += $sale['total_amount'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pettyCash = floatval($_POST['petty_cash'] ?? 0);
    $physicalCash = floatval($_POST['physical_cash'] ?? 0);

    $deficit = $physicalCash - $totalCashSales;
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
            $totalCashSales, // Only cash sales for reconciliation
            $physicalCash,
            $pettyCash,
            $deficit,
            $totalCash
        ]);

        $_SESSION['message'] = "Daily sales report submitted successfully!";
        header("Location: daily_sales_report.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error saving report: " . $e->getMessage();
    }
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .summary-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .highlight {
            font-weight: bold;
            font-size: 1.1em;
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
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Daily Sales Report - <?= date('F j, Y') ?></h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="summary-card">
                    <h4>Sales Summary</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Time</th>
                                <th>Payment Method</th>
                                <th>Amount (D)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td>#<?= $sale['id'] ?></td>
                                    <td><?= date('H:i', strtotime($sale['sale_date'])) ?></td>
                                    <td>
                                        <span class="payment-method-badge 
                                            <?= $sale['payment_method'] === 'cash' ? 'cash-badge' : ($sale['payment_method'] === 'credit' ? 'credit-badge' : 'mobile-badge') ?>">
                                            <?= ucfirst(str_replace('_', ' ', $sale['payment_method'])) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($sale['total_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-primary">
                                <td colspan="3" class="text-end"><strong>Total All Sales:</strong></td>
                                <td class="highlight">D<?= number_format($totalExpected, 2) ?></td>
                            </tr>
                            <tr class="table-success">
                                <td colspan="3" class="text-end"><strong>Total Cash Sales:</strong></td>
                                <td class="highlight">D<?= number_format($totalCashSales, 2) ?></td>
                            </tr>
                            <tr class="table-warning">
                                <td colspan="3" class="text-end"><strong>Total Credit Sales:</strong></td>
                                <td>D<?= number_format($totalCreditSales, 2) ?></td>
                            </tr>
                            <tr class="table-info">
                                <td colspan="3" class="text-end"><strong>Total Mobile Money:</strong></td>
                                <td>D<?= number_format($totalMobileMoneySales, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalCashSales > 0): ?>
                    <form method="POST">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h4>Cash Reconciliation</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <strong>Note:</strong> Only cash sales should be reconciled with physical cash.
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Physical Cash Count (D)</label>
                                        <input type="number" step="0.01" class="form-control" name="physical_cash" required>
                                        <small class="text-muted">Count all cash received today</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Petty Cash (D)</label>
                                        <input type="number" step="0.01" class="form-control" name="petty_cash" value="0">
                                        <small class="text-muted">Starting petty cash amount</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Submit Daily Report</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        No cash sales today. Reconciliation not needed.
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="summary-card bg-light">
                    <h4>Summary</h4>
                    <table class="table">
                        <tr>
                            <th>Total Sales:</th>
                            <td>D<?= number_format($totalExpected, 2) ?></td>
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
                        <?php if ($totalCashSales > 0): ?>
                            <tr class="table-warning">
                                <th>Deficit/Surplus:</th>
                                <td>D<span id="deficitDisplay">0.00</span></td>
                            </tr>
                            <tr class="table-success">
                                <th>Total Cash:</th>
                                <td>D<span id="totalCashDisplay">0.00</span></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate and display values in real-time
        const physicalCashInput = document.querySelector('input[name="physical_cash"]');
        const pettyCashInput = document.querySelector('input[name="petty_cash"]');

        if (physicalCashInput && pettyCashInput) {
            physicalCashInput.addEventListener('input', updateCalculations);
            pettyCashInput.addEventListener('input', updateCalculations);

            function updateCalculations() {
                const physicalCash = parseFloat(physicalCashInput.value) || 0;
                const pettyCash = parseFloat(pettyCashInput.value) || 0;
                const cashSalesAmount = <?= $totalCashSales ?>;

                const deficit = cashSalesAmount - physicalCash;
                const totalCash = physicalCash + pettyCash;

                document.getElementById('deficitDisplay').textContent = deficit.toFixed(2);
                document.getElementById('totalCashDisplay').textContent = totalCash.toFixed(2);
            }
        }
    </script>
</body>

</html>