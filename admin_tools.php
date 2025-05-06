<?php
require_once 'auth.php';
require_once 'functions.php';

// Only allow admins to access this page
if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access admin tools";
    header("Location: dashboard.php");
    exit();
}

// Tables to preserve when clearing data
$preservedTables = ['users', 'employee_permissions'];

// Handle export request
if (isset($_GET['export'])) {
    // Get all tables except preserved ones
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tables = array_diff($tables, $preservedTables);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=fish_inventory_export_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    foreach ($tables as $table) {
        fputcsv($output, ["Table: $table"]);

        // Get column names
        $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
        fputcsv($output, $columns);

        // Get data
        $data = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fputcsv($output, [""]); // Empty row between tables
    }

    fclose($output);
    exit();
}

// Handle clear data request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_data'])) {
    // Verify confirmation text
    if ($_POST['confirmation'] !== 'DELETE ALL') {
        $_SESSION['error'] = "Confirmation text did not match";
        header("Location: admin_tools.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Get all tables except preserved ones
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tables = array_diff($tables, $preservedTables);

        // Disable foreign key checks temporarily
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE $table");
        }

        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        $pdo->commit();

        $_SESSION['message'] = "All data except users and employee permissions has been cleared successfully";
        header("Location: admin_tools.php");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Error clearing data: " . $e->getMessage();
        header("Location: admin_tools.php");
        exit();
    }
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tools - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-card {
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }

        .admin-card .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .danger-zone {
            border: 2px solid #dc3545;
        }

        .danger-zone .card-header {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Admin Tools</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Data Export Card -->
        <div class="card admin-card">
            <div class="card-header">
                <h4 class="mb-0">Data Export</h4>
            </div>
            <div class="card-body">
                <p>Export all database data (except users and employee permissions) to CSV format.</p>
                <a href="admin_tools.php?export=1" class="btn btn-primary">
                    <i class="bi bi-download"></i> Export All Data
                </a>
            </div>
        </div>

        <!-- Data Clear Card -->
        <div class="card admin-card danger-zone">
            <div class="card-header">
                <h4 class="mb-0">Danger Zone: Clear All Data</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <strong>Warning:</strong> This will permanently delete all data except:
                    <ul>
                        <li>User accounts</li>
                        <li>Employee permissions</li>
                    </ul>
                    Make sure you have exported the data before proceeding.
                </div>

                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete ALL data except users and employee permissions? This cannot be undone!')">
                    <div class="mb-3">
                        <label for="confirmation" class="form-label">
                            Type "DELETE ALL" to confirm
                        </label>
                        <input type="text" class="form-control" id="confirmation" name="confirmation" required>
                    </div>
                    <button type="submit" name="clear_data" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Clear All Data
                    </button>
                </form>
            </div>
        </div>

        <!-- Database Info Card -->
        <div class="card admin-card">
            <div class="card-header">
                <h4 class="mb-0">Database Information</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Row Count</th>
                                <th>Size</th>
                                <th>Preserved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tables = $pdo->query("SHOW TABLE STATUS")->fetchAll(PDO::FETCH_ASSOC);
                            $totalSize = 0;

                            foreach ($tables as $table):
                                $sizeKB = round($table['Data_length'] / 1024, 2);
                                $totalSize += $sizeKB;
                                $isPreserved = in_array($table['Name'], $preservedTables);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($table['Name']) ?></td>
                                    <td><?= number_format($table['Rows']) ?></td>
                                    <td><?= $sizeKB ?> KB</td>
                                    <td><?= $isPreserved ? '✅ Yes' : '❌ No' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-primary">
                                <td><strong>Total</strong></td>
                                <td></td>
                                <td><strong><?= round($totalSize, 2) ?> KB (<?= round($totalSize / 1024, 2) ?> MB)</strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>