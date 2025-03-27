<?php
require_once 'navbar.php';
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

// Get current detailed costs
$currentCosts = [];
$stmt = $pdo->query("
    SELECT dc.*, ft.name 
    FROM detailed_costs dc
    JOIN fish_types ft ON dc.fish_type_id = ft.id
    WHERE dc.date_recorded = (SELECT MAX(date_recorded) FROM detailed_costs)
");
$currentCosts = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

// Get all fish types
$fishTypes = getAllFishTypes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $costData = $_POST['costs'];

    try {
        $pdo->beginTransaction();

        foreach ($costData as $fishTypeId => $components) {
            // Calculate total cost per kg
            $totalCost = array_sum($components);

            if ($totalCost > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO detailed_costs (
                        fish_type_id, date_recorded,
                        feed_cost, labor_cost, transport_cost, 
                        medication_cost, equipment_cost, aeration_cost,
                        other_cost, total_cost
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $fishTypeId,
                    $date,
                    $components['feed'] ?? 0,
                    $components['labor'] ?? 0,
                    $components['transport'] ?? 0,
                    $components['medication'] ?? 0,
                    $components['equipment'] ?? 0,
                    $components['aeration'] ?? 0,
                    $components['other'] ?? 0,
                    $totalCost
                ]);
            }
        }

        $pdo->commit();
        $_SESSION['message'] = "Detailed production costs updated successfully!";
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
    <title>Detailed Cost Management - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cost-component {
            margin-bottom: 15px;
        }

        .cost-component label {
            font-weight: 500;
        }

        .total-cost {
            font-weight: bold;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Detailed Production Cost Management</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Update Detailed Production Costs</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="date" class="form-label">Effective Date</label>
                        <input type="date" class="form-control" id="date" name="date"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <?php foreach ($fishTypes as $fish): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5><?= htmlspecialchars($fish['name']) ?></h5>
                            </div>
                            <div class="card-body">
                                <!-- Feed Cost -->
                                <div class="cost-component">
                                    <label class="form-label">Feed Cost </label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="costs[<?= $fish['id'] ?>][feed]"
                                        value="<?= $currentCosts[$fish['id']]['feed_cost'] ?? 0 ?>" required>
                                </div>

                                <!-- Labor Cost -->
                                <div class="cost-component">
                                    <label class="form-label">Labor Cost </label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="costs[<?= $fish['id'] ?>][labor]"
                                        value="<?= $currentCosts[$fish['id']]['labor_cost'] ?? 0 ?>" required>
                                </div>

                                <!-- Transport Cost -->
                                <div class="cost-component">
                                    <label class="form-label">Transport Cost </label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="costs[<?= $fish['id'] ?>][transport]"
                                        value="<?= $currentCosts[$fish['id']]['transport_cost'] ?? 0 ?>" required>
                                </div>

                                <!-- Medication Cost -->
                                <div class="cost-component">
                                    <label class="form-label">Medication Cost </label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="costs[<?= $fish['id'] ?>][medication]"
                                        value="<?= $currentCosts[$fish['id']]['medication_cost'] ?? 0 ?>" required>
                                </div>

                                <!-- Equipment Cost -->
                                <div class="cost-component">
                                    <label class="form-label">Equipment Cost </label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="costs[<?= $fish['id'] ?>][equipment]"
                                        value="<?= $currentCosts[$fish['id']]['equipment_cost'] ?? 0 ?>" required>
                                </div>

                                <!-- Aeration Cost -->
                                <div class="cost-component">
                                    <label class="form-label">Aeration Cost </label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="costs[<?= $fish['id'] ?>][aeration]"
                                        value="<?= $currentCosts[$fish['id']]['aeration_cost'] ?? 0 ?>" required>
                                </div>

                                <!-- Other Costs -->
                                <div class="cost-component">
                                    <label class="form-label">Other Costs </label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        name="costs[<?= $fish['id'] ?>][other]"
                                        value="<?= $currentCosts[$fish['id']]['other_cost'] ?? 0 ?>" required>
                                </div>

                                <!-- Total Cost Preview -->
                                <div class="total-cost mt-3">
                                    <span>Estimated Total Cost/kg: </span>
                                    <span id="total_<?= $fish['id'] ?>">
                                        D<?= number_format($currentCosts[$fish['id']]['total_cost'] ?? 0, 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-primary">Update All Costs</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Cost History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Fish Type</th>
                                <th>Feed</th>
                                <th>Labor</th>
                                <th>Transport</th>
                                <th>Medication</th>
                                <th>Equipment</th>
                                <th>Aeration</th>
                                <th>Other</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("
                                SELECT dc.*, ft.name as fish_name
                                FROM detailed_costs dc
                                JOIN fish_types ft ON dc.fish_type_id = ft.id
                                ORDER BY date_recorded DESC, fish_type_id
                                LIMIT 20
                            ");
                            $history = $stmt->fetchAll();

                            foreach ($history as $record):
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($record['date_recorded']) ?></td>
                                    <td><?= htmlspecialchars($record['fish_name']) ?></td>
                                    <td>D<?= number_format($record['feed_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['labor_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['transport_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['medication_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['equipment_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['aeration_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['other_cost'], 2) ?></td>
                                    <td class="fw-bold">D<?= number_format($record['total_cost'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate and update totals when input values change
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function() {
                const fishId = this.name.match(/\[(\d+)\]/)[1];
                const inputs = document.querySelectorAll(`input[name^="costs[${fishId}]"]`);
                let total = 0;

                inputs.forEach(input => {
                    total += parseFloat(input.value) || 0;
                });

                document.getElementById(`total_${fishId}`).textContent = 'D' + total.toFixed(2);
            });
        });
    </script>
</body>

</html>