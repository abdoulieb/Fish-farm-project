<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Helper function to safely convert to float
function toFloat($value)
{
    if (is_null($value)) return 0.0;
    if ($value === '') return 0.0;
    return (float)$value;
}

// Get all sales data with fish type information
$salesStmt = $pdo->query("
    SELECT 
        s.id,
        s.sale_date,
        s.total_amount as revenue,
        u.username as employee_name,
        GROUP_CONCAT(ft.name SEPARATOR ', ') as fish_names,
        SUM(si.quantity_kg) as total_kg,
        s.payment_method
    FROM 
        sales s
    JOIN 
        users u ON s.employee_id = u.id
    LEFT JOIN 
        sale_items si ON s.id = si.sale_id
    LEFT JOIN
        fish_types ft ON si.fish_type_id = ft.id
    GROUP BY 
        s.id
    ORDER BY 
        s.sale_date DESC
");
$allSales = $salesStmt->fetchAll();

// Get all orders data with fish type information
$ordersStmt = $pdo->query("
    SELECT 
        o.id,
        o.order_date,
        o.total_amount as revenue,
        u.username as customer_name,
        GROUP_CONCAT(ft.name SEPARATOR ', ') as fish_names,
        SUM(oi.quantity_kg) as total_kg,
        o.status
    FROM 
        orders o
    JOIN 
        users u ON o.user_id = u.id
    LEFT JOIN 
        order_items oi ON o.id = oi.order_id
    LEFT JOIN
        fish_types ft ON oi.fish_type_id = ft.id
    WHERE 
        o.status = 'completed'
    GROUP BY 
        o.id
    ORDER BY 
        o.order_date DESC
");
$allOrders = $ordersStmt->fetchAll();

// Get the sum of running costs from detailed_costs table
$runningCostsStmt = $pdo->query("
    SELECT 
        SUM(running_cost) as total_running_cost,
        COUNT(DISTINCT fish_type_id) as fish_types_count
    FROM 
        (SELECT 
            fish_type_id, 
            running_cost,
            date_recorded,
            ROW_NUMBER() OVER (PARTITION BY fish_type_id ORDER BY date_recorded DESC) as rn
         FROM 
            detailed_costs
        ) latest
    WHERE 
        rn = 1
");
$runningCostsData = $runningCostsStmt->fetch();
$totalRunningCost = toFloat($runningCostsData['total_running_cost']);

// Calculate total revenue from sales and completed orders
$totalRevenue = 0;
foreach ($allSales as $sale) {
    $totalRevenue += $sale['revenue'];
}
foreach ($allOrders as $order) {
    $totalRevenue += $order['revenue'];
}

// Calculate profits based on the provided formulas
$totalProfit = $totalRevenue - $totalRunningCost;
$profitPercent = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
$profitPerMonth = $totalProfit / 6;
$profitPercentPerMonth = $profitPercent / 6;

// Get fish performance data
$fishPerformanceStmt = $pdo->query("
    SELECT 
        ft.id,
        ft.name,
        SUM(COALESCE(si.quantity_kg, 0) + COALESCE(oi.quantity_kg, 0)) as total_kg,
        SUM(COALESCE(s.total_amount, 0) + COALESCE(o.total_amount, 0)) as revenue,
        COALESCE(rc.running_cost, 0) as running_cost,
        SUM(COALESCE(s.total_amount, 0) + COALESCE(o.total_amount, 0)) - 
        COALESCE(rc.running_cost, 0) as profit
    FROM 
        fish_types ft
    LEFT JOIN 
        sale_items si ON ft.id = si.fish_type_id
    LEFT JOIN 
        sales s ON si.sale_id = s.id
    LEFT JOIN 
        order_items oi ON ft.id = oi.fish_type_id
    LEFT JOIN 
        orders o ON oi.order_id = o.id AND o.status = 'completed'
    LEFT JOIN 
        (SELECT 
            dc.fish_type_id,
            dc.running_cost
         FROM 
            detailed_costs dc
         JOIN 
            (SELECT fish_type_id, MAX(date_recorded) as latest_date 
             FROM detailed_costs GROUP BY fish_type_id) latest 
         ON dc.fish_type_id = latest.fish_type_id AND dc.date_recorded = latest.latest_date
        ) rc ON ft.id = rc.fish_type_id
    GROUP BY 
        ft.id
    ORDER BY 
        profit DESC
");
$fishPerformance = $fishPerformanceStmt->fetchAll();

// Recent sales for display
$recentSales = array_slice($allSales, 0, 10);
// Recent orders for display
$recentOrders = array_slice($allOrders, 0, 10);
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

        .cost-breakdown {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .cost-breakdown-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .monthly-metrics {
            background-color: #e9ecef;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Profit Analysis Dashboard</h2>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="summary-card bg-light">
                    <h5>Total Revenue</h5>
                    <p class="h4">D<?= number_format($totalRevenue, 2) ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-light">
                    <h5>Total Running Cost</h5>
                    <p class="h4">D<?= number_format($totalRunningCost, 2) ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card <?= $totalProfit >= 0 ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                    <h5>Total Profit</h5>
                    <p class="h4">D<?= number_format($totalProfit, 2) ?></p>
                    <p class="h5"><?= number_format($profitPercent, 2) ?>%</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card <?= $profitPerMonth >= 0 ? 'bg-info text-white' : 'bg-warning text-white' ?>">
                    <h5>Profit Per Month (6 months)</h5>
                    <p class="h4">D<?= number_format($profitPerMonth, 2) ?></p>
                    <p class="h5"><?= number_format($profitPercentPerMonth, 2) ?>%</p>
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
                                <th>Running Cost</th>
                                <th>Profit</th>
                                <th>Profit %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fishPerformance as $fish):
                                $fishProfitPercent = $fish['revenue'] > 0 ? ($fish['profit'] / $fish['revenue']) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($fish['name']) ?></td>
                                    <td><?= number_format($fish['total_kg'], 2) ?></td>
                                    <td>D<?= number_format($fish['revenue'], 2) ?></td>
                                    <td>D<?= number_format($fish['running_cost'], 2) ?></td>
                                    <td class="<?= $fish['profit'] >= 0 ? 'positive' : 'negative' ?>">
                                        D<?= number_format($fish['profit'], 2) ?>
                                    </td>
                                    <td class="<?= $fishProfitPercent >= 0 ? 'positive' : 'negative' ?>">
                                        <?= number_format($fishProfitPercent, 2) ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Recent Sales</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Fish Types</th>
                                <th>Total (kg)</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td>#<?= $sale['id'] ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($sale['sale_date'])) ?></td>
                                    <td><?= htmlspecialchars($sale['employee_name']) ?></td>
                                    <td><?= htmlspecialchars($sale['fish_names']) ?></td>
                                    <td><?= number_format($sale['total_kg'], 2) ?></td>
                                    <td>D<?= number_format($sale['revenue'], 2) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $sale['payment_method'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Recent Completed Orders</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Fish Types</th>
                                <th>Total (kg)</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['fish_names']) ?></td>
                                    <td><?= number_format($order['total_kg'], 2) ?></td>
                                    <td>D<?= number_format($order['revenue'], 2) ?></td>
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
                        label: 'Running Cost',
                        data: [<?= implode(',', array_column($fishPerformance, 'running_cost')) ?>],
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