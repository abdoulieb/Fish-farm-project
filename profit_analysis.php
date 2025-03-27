<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

// Get profit analysis data with detailed breakdown
$stmt = $pdo->query("
    SELECT 
        o.id,
        DATE(o.order_date) as order_date,
        u.username,
        ft.name as fish_name,
        oi.quantity_kg,
        oi.unit_price as sale_price,
        dc.feed_cost,
        dc.labor_cost,
        dc.transport_cost,
        dc.medication_cost,
        dc.equipment_cost,
        dc.aeration_cost,
        dc.other_cost,
        (oi.quantity_kg * oi.unit_price) as revenue,
        (oi.quantity_kg * (dc.feed_cost + dc.labor_cost + dc.transport_cost + 
          dc.medication_cost + dc.equipment_cost + dc.aeration_cost + dc.other_cost)) as total_cost,
        (oi.quantity_kg * oi.unit_price) - 
        (oi.quantity_kg * (dc.feed_cost + dc.labor_cost + dc.transport_cost + 
          dc.medication_cost + dc.equipment_cost + dc.aeration_cost + dc.other_cost)) as profit,
        CASE 
            WHEN (oi.quantity_kg * oi.unit_price) > 0 
            THEN (((oi.quantity_kg * oi.unit_price) - 
                  (oi.quantity_kg * (dc.feed_cost + dc.labor_cost + dc.transport_cost + 
                   dc.medication_cost + dc.equipment_cost + dc.aeration_cost + dc.other_cost))) / 
                  (oi.quantity_kg * oi.unit_price)) * 100
            ELSE 0
        END as margin
    FROM 
        orders o
    JOIN 
        users u ON o.user_id = u.id
    JOIN 
        order_items oi ON o.id = oi.order_id
    JOIN 
        fish_types ft ON oi.fish_type_id = ft.id
    JOIN 
        (SELECT fish_type_id, MAX(date_recorded) as latest_date 
         FROM detailed_costs GROUP BY fish_type_id) latest ON ft.id = latest.fish_type_id
    JOIN 
        detailed_costs dc ON ft.id = dc.fish_type_id AND dc.date_recorded = latest.latest_date
    ORDER BY 
        o.order_date DESC
");
$orders = $stmt->fetchAll();

// Summary statistics with period filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filter_condition = "";
if ($filter == 'month') {
    $filter_condition = "WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
} elseif ($filter == 'quarter') {
    $filter_condition = "WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
}

$stmt = $pdo->query("
    SELECT 
        SUM(oi.quantity_kg * oi.unit_price) AS total_revenue,
        SUM(oi.quantity_kg * (dc.feed_cost + dc.labor_cost + dc.transport_cost + 
            dc.medication_cost + dc.equipment_cost + dc.aeration_cost + dc.other_cost)) AS total_cost,
        SUM(oi.quantity_kg * oi.unit_price) - 
        SUM(oi.quantity_kg * (dc.feed_cost + dc.labor_cost + dc.transport_cost + 
            dc.medication_cost + dc.equipment_cost + dc.aeration_cost + dc.other_cost)) AS total_profit,
        AVG(
            CASE 
                WHEN (oi.quantity_kg * oi.unit_price) > 0 
                THEN (((oi.quantity_kg * oi.unit_price) - 
                      (oi.quantity_kg * (dc.feed_cost + dc.labor_cost + dc.transport_cost + 
                       dc.medication_cost + dc.equipment_cost + dc.aeration_cost + dc.other_cost))) / 
                      (oi.quantity_kg * oi.unit_price)) * 100
                ELSE 0
            END
        ) AS avg_margin,
        COUNT(DISTINCT o.id) AS total_orders,
        SUM(oi.quantity_kg) AS total_kg_sold
    FROM 
        orders o
    JOIN 
        order_items oi ON o.id = oi.order_id
    JOIN 
        fish_types ft ON oi.fish_type_id = ft.id
    JOIN 
        (SELECT fish_type_id, MAX(date_recorded) as latest_date 
         FROM detailed_costs GROUP BY fish_type_id) latest ON ft.id = latest.fish_type_id
    JOIN 
        detailed_costs dc ON ft.id = dc.fish_type_id AND dc.date_recorded = latest.latest_date
    $filter_condition
");
$summary = $stmt->fetch();

// Fish type performance
$stmt = $pdo->query("
    SELECT 
        ft.name,
        SUM(oi.quantity_kg) as total_kg,
        SUM(oi.quantity_kg * oi.unit_price) as revenue,
        SUM(oi.quantity_kg * (dc.feed_cost + dc.labor_cost + dc.transport_cost + 
            dc.medication_cost + dc.equipment_cost + dc.aeration_cost + dc.other_cost)) as cost,
        SUM(oi.quantity_kg * oi.unit_price) - 
        SUM(oi.quantity_kg * (dc.feed_cost + dc.labor_cost + dc.transport_cost + 
            dc.medication_cost + dc.equipment_cost + dc.aeration_cost + dc.other_cost)) as profit
    FROM 
        order_items oi
    JOIN 
        fish_types ft ON oi.fish_type_id = ft.id
    JOIN 
        (SELECT fish_type_id, MAX(date_recorded) as latest_date 
         FROM detailed_costs GROUP BY fish_type_id) latest ON ft.id = latest.fish_type_id
    JOIN 
        detailed_costs dc ON ft.id = dc.fish_type_id AND dc.date_recorded = latest.latest_date
    GROUP BY 
        ft.id
    ORDER BY 
        profit DESC
");
$fishPerformance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit Analysis - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
        .summary-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .positive {
            color: #28a745;
            font-weight: bold;
        }

        .negative {
            color: #dc3545;
            font-weight: bold;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }

        .filter-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Profit Analysis Dashboard</h2>

        <!-- Filter Buttons -->
        <div class="filter-buttons mb-4">
            <a href="?filter=all" class="btn btn-outline-primary <?= $filter == 'all' ? 'active' : '' ?>">All Time</a>
            <a href="?filter=month" class="btn btn-outline-primary <?= $filter == 'month' ? 'active' : '' ?>">Last Month</a>
            <a href="?filter=quarter" class="btn btn-outline-primary <?= $filter == 'quarter' ? 'active' : '' ?>">Last Quarter</a>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="summary-card bg-light">
                    <h5>Total Revenue</h5>
                    <p class="h4">D<?= number_format($summary['total_revenue'], 2) ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-light">
                    <h5>Total Cost</h5>
                    <p class="h4">D<?= number_format($summary['total_cost'], 2) ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card <?= $summary['total_profit'] >= 0 ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                    <h5>Total Profit</h5>
                    <p class="h4">D<?= number_format($summary['total_profit'], 2) ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card <?= $summary['avg_margin'] >= 0 ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                    <h5>Avg Margin</h5>
                    <p class="h4"><?= number_format($summary['avg_margin'], 2) ?>%</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Profit by Fish Type</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="fishProfitChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Revenue vs Cost</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueCostChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fish Performance Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Fish Type Performance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fish Type</th>
                                <th>Quantity (kg)</th>
                                <th>Revenue</th>
                                <th>Cost</th>
                                <th>Profit</th>
                                <th>Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fishPerformance as $fish):
                                $margin = ($fish['revenue'] > 0) ? (($fish['profit'] / $fish['revenue']) * 100) : 0;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($fish['name']) ?></td>
                                    <td><?= number_format($fish['total_kg'], 2) ?></td>
                                    <td>D<?= number_format($fish['revenue'], 2) ?></td>
                                    <td>D<?= number_format($fish['cost'], 2) ?></td>
                                    <td class="<?= $fish['profit'] >= 0 ? 'positive' : 'negative' ?>">
                                        D<?= number_format($fish['profit'], 2) ?>
                                    </td>
                                    <td class="<?= $margin >= 0 ? 'positive' : 'negative' ?>">
                                        <?= number_format($margin, 2) ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detailed Orders Table -->
        <div class="card mt-4">
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
                                <th>Fish Type</th>
                                <th>Qty (kg)</th>
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
                                    <td><?= htmlspecialchars($order['order_date']) ?></td>
                                    <td><?= htmlspecialchars($order['username']) ?></td>
                                    <td><?= htmlspecialchars($order['fish_name']) ?></td>
                                    <td><?= number_format($order['quantity_kg'], 2) ?></td>
                                    <td>D<?= number_format($order['revenue'], 2) ?></td>
                                    <td>D<?= number_format($order['total_cost'], 2) ?></td>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Fish Profit Chart
        const fishProfitCtx = document.getElementById('fishProfitChart').getContext('2d');
        const fishProfitChart = new Chart(fishProfitCtx, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(function ($fish) {
                                return "'" . htmlspecialchars($fish['name']) . "'";
                            }, $fishPerformance)) ?>],
                datasets: [{
                    label: 'Profit (D)',
                    data: [<?= implode(',', array_column($fishPerformance, 'profit')) ?>],
                    backgroundColor: [
                        <?php foreach ($fishPerformance as $fish): ?> '<?= $fish['profit'] >= 0 ? "rgba(40, 167, 69, 0.7)" : "rgba(220, 53, 69, 0.7)" ?>',
                        <?php endforeach; ?>
                    ],
                    borderColor: [
                        <?php foreach ($fishPerformance as $fish): ?> '<?= $fish['profit'] >= 0 ? "rgba(40, 167, 69, 1)" : "rgba(220, 53, 69, 1)" ?>',
                        <?php endforeach; ?>
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Profit: D' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'D' + value;
                            }
                        }
                    }
                }
            }
        });

        // Revenue vs Cost Chart
        const revenueCostCtx = document.getElementById('revenueCostChart').getContext('2d');
        const revenueCostChart = new Chart(revenueCostCtx, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(function ($fish) {
                                return "'" . htmlspecialchars($fish['name']) . "'";
                            }, $fishPerformance)) ?>],
                datasets: [{
                        label: 'Revenue',
                        data: [<?= implode(',', array_column($fishPerformance, 'revenue')) ?>],
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Cost',
                        data: [<?= implode(',', array_column($fishPerformance, 'cost')) ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'D' + value;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>