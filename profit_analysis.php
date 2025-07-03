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

// Get all sales data
$salesStmt = $pdo->query("
    SELECT 
        s.id,
        s.sale_date,
        s.total_amount as revenue,
        u.username as employee_name,
        GROUP_CONCAT(ft.name SEPARATOR ', ') as fish_names,
        SUM(si.quantity_kg) as total_kg,
        s.payment_method
    FROM sales s
    JOIN users u ON s.employee_id = u.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN fish_types ft ON si.fish_type_id = ft.id
    GROUP BY s.id
    ORDER BY s.sale_date DESC
");
$allSales = $salesStmt->fetchAll();

// Get all orders data
$ordersStmt = $pdo->query("
    SELECT 
        o.id,
        o.order_date,
        o.total_amount as revenue,
        u.username as customer_name,
        GROUP_CONCAT(ft.name SEPARATOR ', ') as fish_names,
        SUM(oi.quantity_kg) as total_kg,
        o.status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN fish_types ft ON oi.fish_type_id = ft.id
    WHERE o.status = 'completed'
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$allOrders = $ordersStmt->fetchAll();

// Get running costs
$runningCostsStmt = $pdo->query("
    SELECT 
        SUM(running_cost) as total_running_cost
    FROM (
        SELECT 
            fish_type_id, 
            running_cost,
            date_recorded,
            ROW_NUMBER() OVER (PARTITION BY fish_type_id ORDER BY date_recorded DESC) as rn
        FROM detailed_costs
    ) latest
    WHERE rn = 1
");
$runningCostsData = $runningCostsStmt->fetch();
$totalRunningCost = toFloat($runningCostsData['total_running_cost']);

// Calculate total revenue
$totalRevenue = 0;
foreach ($allSales as $sale) $totalRevenue += $sale['revenue'];
foreach ($allOrders as $order) $totalRevenue += $order['revenue'];

// Calculate profits with inflation
$inflatedTotalCost = $totalRunningCost * 1.36;
$totalProfit = $totalRevenue - $inflatedTotalCost;
$profitPercent = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

// Get fish performance data (consistent with fatality_records.php)
$fishPerformanceStmt = $pdo->query("
    SELECT 
        ft.id,
        ft.name,
        COALESCE((
            SELECT SUM(dc.fingerlings_quantity) 
            FROM detailed_costs dc 
            WHERE dc.fish_type_id = ft.id
        ), 0) as total_fingerlings,
        COALESCE(SUM(ff.quantity), 0) as total_dead,
        (COALESCE((
            SELECT SUM(dc.fingerlings_quantity) 
            FROM detailed_costs dc 
            WHERE dc.fish_type_id = ft.id
        ), 0) - COALESCE(SUM(ff.quantity), 0)) as total_alive,
        COALESCE((
            SELECT dc.running_cost 
            FROM detailed_costs dc 
            WHERE dc.fish_type_id = ft.id
            ORDER BY dc.date_recorded DESC 
            LIMIT 1
        ), 0) as running_cost,
        SUM(COALESCE(si.quantity_kg, 0) + COALESCE(oi.quantity_kg, 0)) as total_kg,
        SUM(COALESCE(s.total_amount, 0) + COALESCE(o.total_amount, 0)) as revenue
    FROM fish_types ft
    LEFT JOIN sale_items si ON ft.id = si.fish_type_id
    LEFT JOIN sales s ON si.sale_id = s.id
    LEFT JOIN order_items oi ON ft.id = oi.fish_type_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
    LEFT JOIN fish_fatalities ff ON ft.id = ff.fish_type_id
    GROUP BY ft.id, ft.name
    ORDER BY ft.name
");
$fishPerformance = $fishPerformanceStmt->fetchAll();

// Calculate profitability with consistent 36% inflation
$profitabilityReport = [];
foreach ($fishPerformance as $fish) {
    if ($fish['total_fingerlings'] > 0) {
        $inflatedCost = $fish['running_cost'] * 1.36;
        $avgWeightKg = 0.3;
        $totalKgAlive = $fish['total_alive'] * $avgWeightKg;

        if ($totalKgAlive > 0) {
            $costPerKg = $inflatedCost / $totalKgAlive;
            $profit = $fish['revenue'] - $inflatedCost;
            $profitPercent = $fish['revenue'] > 0 ? ($profit / $fish['revenue']) * 100 : 0;

            $profitabilityReport[$fish['id']] = [
                'total_kg' => $totalKgAlive,
                'running_cost' => $fish['running_cost'],
                'inflated_cost' => $inflatedCost,
                'cost_per_kg' => $costPerKg,
                'price_10' => $costPerKg * 1.10,
                'price_20' => $costPerKg * 1.20,
                'price_30' => $costPerKg * 1.30,
                'price_40' => $costPerKg * 1.40,
                'price_50' => $costPerKg * 1.50,
                'profit' => $profit,
                'profit_percent' => $profitPercent
            ];
        }
    }
}

// Recent data for display
$recentSales = array_slice($allSales, 0, 10);
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

        .profit-badge {
            font-size: 0.8rem;
            padding: 0.25em 0.4em;
        }

        .table th {
            white-space: nowrap;
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
                    <h5>Total Profit (36% inflation)</h5>
                    <p class="h4">D<?= number_format($totalProfit, 2) ?></p>
                    <p class="h5"><?= number_format($profitPercent, 2) ?>%</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-info text-white">
                    <h5>Avg. Cost per kg</h5>
                    <p class="h4">D<?= number_format($totalRunningCost > 0 ? ($inflatedTotalCost / array_sum(array_column($profitabilityReport, 'total_kg'))) : 0, 2) ?></p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Profit by Fish Type (36% inflation)</h5>
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
                        <h5>Revenue vs Inflated Cost</h5>
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
                <h5>Fish Type Performance (with 36% inflation)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fish Type</th>
                                <th>Total Alive (kg)</th>
                                <th>Revenue</th>
                                <th>Original Cost</th>
                                <th>Inflated Cost</th>
                                <th>Profit</th>
                                <th>Profit %</th>
                                <th>Target Prices</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tbody>
                            <?php foreach ($fishPerformance as $fish):
                                $profitData = $profitabilityReport[$fish['id']] ?? null;
                                if ($profitData): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fish['name']) ?></td>
                                        <td><?= number_format($profitData['total_kg'], 2) ?></td>
                                        <td>D<?= number_format($fish['revenue'], 2) ?></td>
                                        <td>D<?= number_format($fish['running_cost'], 2) ?></td>
                                        <td>D<?= number_format($profitData['inflated_cost'], 2) ?></td>
                                        <td class="<?= $profitData['profit'] >= 0 ? 'positive' : 'negative' ?>">
                                            D<?= number_format($profitData['profit'], 2) ?>
                                        </td>
                                        <td class="<?= $profitData['profit_percent'] >= 0 ? 'positive' : 'negative' ?>">
                                            <?= number_format($profitData['profit_percent'], 2) ?>%
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <span class="badge bg-primary profit-badge">10%: D<?= number_format($profitData['price_10'], 2) ?></span>
                                                <span class="badge bg-success profit-badge">20%: D<?= number_format($profitData['price_20'], 2) ?></span>
                                                <span class="badge bg-info profit-badge">30%: D<?= number_format($profitData['price_30'], 2) ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3">
                    <strong>Note:</strong> All calculations include 36% inflation adjustment. Target prices based on cost per kg with inflation.
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
        document.addEventListener('DOMContentLoaded', function() {
            const fishProfitCtx = document.getElementById('fishProfitChart').getContext('2d');

            // Prepare chart data
            const fishLabels = [
                <?php
                foreach ($fishPerformance as $fish) {
                    $profitData = $profitabilityReport[$fish['id']] ?? null;
                    if ($profitData) {
                        echo "'" . htmlspecialchars($fish['name']) . "',";
                    }
                }
                ?>
            ].filter(label => label);

            const fishProfitData = [
                <?php
                foreach ($fishPerformance as $fish) {
                    $profitData = $profitabilityReport[$fish['id']] ?? null;
                    if ($profitData) {
                        echo $profitData['profit'] . ",";
                    }
                }
                ?>
            ].filter(value => value !== undefined);

            const fishBackgroundColors = [
                <?php
                foreach ($fishPerformance as $fish) {
                    $profitData = $profitabilityReport[$fish['id']] ?? null;
                    if ($profitData) {
                        echo $profitData['profit'] >= 0
                            ? "'rgba(40, 167, 69, 0.7)',"
                            : "'rgba(220, 53, 69, 0.7)',";
                    }
                }
                ?>
            ].filter(color => color);

            // Create the chart
            new Chart(fishProfitCtx, {
                type: 'bar',
                data: {
                    labels: fishLabels,
                    datasets: [{
                        label: 'Profit (D)',
                        data: fishProfitData,
                        backgroundColor: fishBackgroundColors,
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
                                    return `Profit: D${context.raw.toFixed(2)}`;
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

            const revenueData = [
                <?php
                foreach ($fishPerformance as $fish) {
                    echo $fish['revenue'] . ",";
                }
                ?>
            ];

            const costData = [
                <?php
                foreach ($fishPerformance as $fish) {
                    $profitData = $profitabilityReport[$fish['id']] ?? null;
                    echo $profitData ? $profitData['inflated_cost'] . "," : "0,";
                }
                ?>
            ];

            new Chart(revenueCostCtx, {
                type: 'bar',
                data: {
                    labels: fishLabels,
                    datasets: [{
                            label: 'Revenue',
                            data: revenueData,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Inflated Cost (36%)',
                            data: costData,
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
        });
    </script>
</body>

</html>