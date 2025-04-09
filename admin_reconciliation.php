<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: index.php");
    exit();
}

// Handle form submission to update reconciliation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reconciliation'])) {
    $reportId = intval($_POST['report_id']);
    $physicalCash = floatval($_POST['physical_cash']);
    $pettyCash = floatval($_POST['petty_cash']);

    try {
        $stmt = $pdo->prepare("
            UPDATE cash_reconciliations 
            SET physical_cash = ?, petty_cash = ?, 
                deficit = expected_amount - ?,
                total_cash = ? + ?
            WHERE id = ?
        ");
        $stmt->execute([
            $physicalCash,
            $pettyCash,
            $physicalCash,
            $physicalCash,
            $pettyCash,
            $reportId
        ]);

        $_SESSION['message'] = "Reconciliation report updated successfully!";
        header("Location: admin_reconciliation.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating report: " . $e->getMessage();
    }
}

// Get all reconciliation reports
$reports = $pdo->query("
    SELECT cr.*, u.username as employee_name 
    FROM cash_reconciliations cr
    JOIN users u ON cr.employee_id = u.id
    ORDER BY cr.report_date DESC
")->fetchAll();

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reconciliation - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Manage Reconciliation Reports</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="table-responsive mt-4">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Expected (D)</th>
                        <th>Physical Cash</th>
                        <th>Petty Cash</th>
                        <th>Total Cash</th>
                        <th>Deficit/Surplus</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= htmlspecialchars($report['employee_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($report['report_date'])) ?></td>
                            <td>D<?= number_format($report['expected_amount'], 2) ?></td>
                            <td>
                                <form method="POST" class="row g-2">
                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                    <div class="col">
                                        <input type="number" step="0.01" class="form-control" name="physical_cash"
                                            value="<?= number_format($report['physical_cash'], 2) ?>" required>
                                    </div>
                            </td>
                            <td>
                                <div class="col">
                                    <input type="number" step="0.01" class="form-control" name="petty_cash"
                                        value="<?= number_format($report['petty_cash'], 2) ?>" required>
                                </div>
                            </td>
                            <td>D<?= number_format($report['total_cash'], 2) ?></td>
                            <td class="<?= $report['deficit'] < 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                D<?= number_format($report['deficit'], 2) ?>
                            </td>
                            <td>
                                <div class="col">
                                    <button type="submit" name="update_reconciliation" class="btn btn-sm btn-primary">
                                        Update
                                    </button>
                                </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>