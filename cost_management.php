<?php

require_once 'navbar.php';
require_once 'auth.php';
require_once 'functions.php';
if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

// Get current costs
$currentCosts = [];
$stmt = $pdo->query("
SELECT pc.*, ft.name
FROM production_costs pc
JOIN fish_types ft ON pc.fish_type_id = ft.id
WHERE pc.date_recorded = (SELECT MAX(date_recorded) FROM production_costs)
");
$currentCosts = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

// Get all fish types
$fishTypes = getAllFishTypes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $costs = $_POST['costs'];

    try {
        $pdo->beginTransaction();

        foreach ($costs as $fishTypeId => $cost) {
            $cost = floatval($cost);
            if ($cost > 0) {
                $stmt = $pdo->prepare("
INSERT INTO production_costs (fish_type_id, cost_per_kg, date_recorded)
VALUES (?, ?, ?)
");
                $stmt->execute([$fishTypeId, $cost, $date]);
            }
        }

        $pdo->commit();
        $_SESSION['message'] = "Production costs updated successfully!";
        header("Location: cost_management.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to update costs: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cost Management - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">
        <h2>Production Cost Management</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Update Production Costs</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="date" class="form-label">Effective Date</label>
                        <input type="date" class="form-control" id="date" name="date"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fish Type</th>
                                <th>Current Cost/kg</th>
                                <th>New Cost/kg</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fishTypes as $fish): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fish['name']) ?></td>
                                    <td>
                                        <?= isset($currentCosts[$fish['id']]) ?
                                            'D' . number_format($currentCosts[$fish['id']]['cost_per_kg'], 2) :
                                            'Not set' ?>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0"
                                            class="form-control"
                                            name="costs[<?= $fish['id'] ?>]"
                                            value="<?= isset($currentCosts[$fish['id']]) ?
                                                        $currentCosts[$fish['id']]['cost_per_kg'] : '' ?>"
                                            required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit" class="btn btn-primary">Update Costs</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Cost History</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <?php foreach ($fishTypes as $fish): ?>
                                <th><?= htmlspecialchars($fish['name']) ?> Cost/kg</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT DISTINCT date_recorded 
                            FROM production_costs 
                            ORDER BY date_recorded DESC
                            LIMIT 10
                        ");
                        $dates = $stmt->fetchAll();

                        foreach ($dates as $dateRow):
                            $date = $dateRow['date_recorded'];
                            $stmt = $pdo->prepare("
                                SELECT fish_type_id, cost_per_kg 
                                FROM production_costs 
                                WHERE date_recorded = ?
                            ");
                            $stmt->execute([$date]);
                            $costs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($date) ?></td>
                                <?php foreach ($fishTypes as $fish): ?>
                                    <td>
                                        <?= isset($costs[$fish['id']]) ?
                                            'D' . number_format($costs[$fish['id']], 2) :
                                            '-' ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>