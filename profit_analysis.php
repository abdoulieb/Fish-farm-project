<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

// Get profit analysis data
$stmt = $pdo->query("
    SELECT 
        o.id,
        o.order_date,
        u.username,
        COALESCE(SUM(oi.quantity_kg * oi.unit_price), 0) AS revenue,
        COALESCE(SUM(oi.quantity_kg * pc.cost_per_kg), 0) AS cost,
        COALESCE(SUM(oi.quantity_kg * oi.unit_price) - SUM(oi.quantity_kg * pc.cost_per_kg), 0) AS profit,
        CASE 
            WHEN SUM(oi.quantity_kg * oi.unit_price) > 0 
            THEN (SUM(oi.quantity_kg * oi.unit_price) - SUM(oi.quantity_kg * pc.cost_per_kg)) / SUM(oi.quantity_kg * oi.unit_price) * 100
            ELSE 0
        END AS margin
    FROM 
        orders o
    JOIN 
        users u ON o.user_id = u.id
    LEFT JOIN 
        order_items oi ON o.id = oi.order_id
    LEFT JOIN 
        (SELECT fish_type_id, cost_per_kg 
         FROM production_costs 
         WHERE date_recorded = (SELECT MAX(date_recorded) FROM production_costs)
        ) pc ON oi.fish_type_id = pc.fish_type_id
    GROUP BY 
        o.id
    ORDER BY 
        o.order_date DESC
");
$orders = $stmt->fetchAll();

// Summary statistics
$stmt = $pdo->query("
    SELECT 
        COALESCE(SUM(revenue), 0) AS total_revenue,
        COALESCE(SUM(cost), 0) AS total_cost,
        COALESCE(SUM(profit), 0) AS total_profit,
        CASE 
            WHEN SUM(revenue) > 0 
            THEN AVG((revenue - cost) / revenue * 100)
            ELSE 0
        END AS avg_margin
    FROM (
        SELECT 
            SUM(oi.quantity_kg * oi.unit_price) AS revenue,
            SUM(oi.quantity_kg * pc.cost_per_kg) AS cost,
            SUM(oi.quantity_kg * oi.unit_price) - SUM(oi.quantity_kg * pc.cost_per_kg) AS profit
        FROM 
            orders o
        JOIN 
            order_items oi ON o.id = oi.order_id
        JOIN 
            (SELECT fish_type_id, cost_per_kg 
             FROM production_costs 
             WHERE date_recorded = (SELECT MAX(date_recorded) FROM production_costs)
            ) pc ON oi.fish_type_id = pc.fish_type_id
        GROUP BY 
            o.id
    ) AS profit_data
");
$summary = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit Analysis - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .positive {
            color: green;
        }

        .negative {
            color: red;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Profit Analysis</h2>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <p class="card-text h4">D<?= number_format($summary['total_revenue'], 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Cost</h5>
                        <p class="card-text h4">D<?= number_format($summary['total_cost'], 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white <?= $summary['total_profit'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                    <div class="card-body">
                        <h5 class="card-title">Total Profit</h5>
                        <p class="card-text h4">D<?= number_format($summary['total_profit'], 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white <?= $summary['avg_margin'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                    <div class="card-body">
                        <h5 class="card-title">Avg Margin</h5>
                        <p class="card-text h4"><?= number_format($summary['avg_margin'], 2) ?>%</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Order Profit Details</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Revenue</th>
                                <th>Cost</th>
                                <th>Profit</th>
                                <th>Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                    <td><?= htmlspecialchars($order['username']) ?></td>
                                    <td>D<?= number_format($order['revenue'], 2) ?></td>
                                    <td>D<?= number_format($order['cost'], 2) ?></td>
                                    <td class="<?= $order['profit'] >= 0 ? 'positive' : 'negative' ?>">
                                        D<?= number_format($order['profit'], 2) ?>
                                    </td>
                                    <td class="<?= $order['margin'] >= 0 ? 'positive' : 'negative' ?>">
                                        <?= number_format($order['margin'], 2) ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>