<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

// Helper function to safely convert to float
function toFloat($value)
{
    if (is_null($value)) return 0.0;
    if ($value === '') return 0.0;
    return (float)$value;
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
        dc.running_cost,
        (oi.quantity_kg * oi.unit_price) as revenue,
        ((oi.quantity_kg * oi.unit_price) - dc.running_cost) as profit,
        (((oi.quantity_kg * oi.unit_price) - dc.running_cost) / (oi.quantity_kg * oi.unit_price)) * 100 as profit_percent
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
         sum(dc.running_cost) AS total_cost,
        (SUM(oi.quantity_kg * oi.unit_price) - SUM(dc.running_cost)) AS total_profit
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

// Calculate metrics
$total_profit = toFloat($summary['total_profit']);
$total_revenue = toFloat($summary['total_revenue']);
$profit_percent = $total_revenue > 0 ? ($total_profit / $total_revenue) * 100 : 0;

$profit_per_month = $total_profit / 6;
$profit_percent_per_month = $profit_percent / 6;

// Fish type performance
$stmt = $pdo->query("
    SELECT 
        ft.name,
        SUM(oi.quantity_kg) as total_kg,
        SUM(oi.quantity_kg * oi.unit_price) as revenue,
        sum(dc.running_cost) as cost,
       
        (SUM(oi.quantity_kg * oi.unit_price) - SUM(dc.running_cost)) as profit,
        CASE
            WHEN SUM(oi.quantity_kg * oi.unit_price) > 0
            THEN (SUM(oi.quantity_kg * oi.unit_price) - SUM(dc.running_cost)) / SUM(oi.quantity_kg * oi.unit_price) * 100
            ELSE 0
        END as profit_percent
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

// Get sales data
$salesStmt = $pdo->query("
    SELECT 
        s.*,
        u.username as employee_name,
        COUNT(si.id) as items_count,
        SUM(si.quantity_kg) as total_kg
    FROM 
        sales s
    JOIN 
        users u ON s.employee_id = u.id
    LEFT JOIN 
        sale_items si ON s.id = si.sale_id
    GROUP BY 
        s.id
    ORDER BY 
        s.sale_date DESC
    LIMIT 10
");
$recentSales = $salesStmt->fetchAll();
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

        <!-- Filter Buttons -->
        <div class="filter-buttons mb-4">
            <a href="?filter=all" class="btn btn-outline-primary <?= $filter == 'all' ? 'active' : '' ?>">All Time</a>
            <a href="?filter=month" class="btn btn-outline-primary <?= $filter == 'month' ? 'active' : '' ?>">Last Month</a>
            <a href="?filter=quarter" class="btn btn-outline-primary <?= $filter == 'quarter' ? 'active' : '' ?>">Last Quarter</a>
        </div>

        <!-- Monthly Metrics Section -->
        <div class="monthly-metrics">
            <h4>Monthly Profit Metrics (6-month basis)</h4>
            <div class="row">
                <div class="col-md-4">
                    <p>Total Profit: <strong>D<?= number_format($total_profit, 2) ?></strong></p>
                    <p>Profit per Month: <strong>D<?= number_format($profit_per_month, 2) ?></strong></p>
                </div>
                <div class="col-md-4">
                    <p>Profit Percentage: <strong><?= number_format($profit_percent, 2) ?>%</strong></p>
                    <p>Monthly Profit Percentage: <strong><?= number_format($profit_percent_per_month, 2) ?>%</strong></p>
                </div>
                <div class="col-md-4">
                    <p>Total Revenue: <strong>D<?= number_format($total_revenue, 2) ?></strong></p>
                    <p>Total Cost: <strong>D<?= number_format($summary['total_cost'], 2) ?></strong></p>
                </div>
            </div>
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
                                <th>Profit %</th>
                                <th>Monthly Profit</th>
                                <th>Monthly Profit %</th>
                                <th>Cost Breakdown</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fishPerformance as $fish):
                                $monthly_profit = $fish['profit'] / 6;
                                $monthly_profit_percent = $fish['profit_percent'] / 6;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($fish['name']) ?></td>
                                    <td><?= number_format($fish['total_kg'], 2) ?></td>
                                    <td>D<?= number_format($fish['revenue'], 2) ?></td>
                                    <td>D<?= number_format($fish['cost'], 2) ?></td>
                                    <td class="<?= $fish['profit'] >= 0 ? 'positive' : 'negative' ?>">
                                        D<?= number_format($fish['profit'], 2) ?>
                                    </td>
                                    <td class="<?= $fish['profit_percent'] >= 0 ? 'positive' : 'negative' ?>">
                                        <?= number_format($fish['profit_percent'], 2) ?>%
                                    </td>
                                    <td class="<?= $monthly_profit >= 0 ? 'positive' : 'negative' ?>">
                                        D<?= number_format($monthly_profit, 2) ?>
                                    </td>
                                    <td class="<?= $monthly_profit_percent >= 0 ? 'positive' : 'negative' ?>">
                                        <?= number_format($monthly_profit_percent, 2) ?>%
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="toggleBreakdown(this)">Show Breakdown</button>
                                        <div class="cost-breakdown" style="display: none;">
                                            <div class="cost-breakdown-item">
                                                <span>Fingerlings:</span>
                                                <span>D<?= number_format($fish['total_fingerlings_cost'] ?? 0, 2) ?></span>
                                            </div>
                                            <div class="cost-breakdown-item">
                                                <span>Feed:</span>
                                                <span>D<?= number_format($fish['total_feed_cost'] ?? 0, 2) ?></span>
                                            </div>
                                            <div class="cost-breakdown-item">
                                                <span>Materials:</span>
                                                <span>D<?= number_format($fish['total_material_cost'] ?? 0, 2) ?></span>
                                            </div>
                                            <div class="cost-breakdown-item">
                                                <span>Transport:</span>
                                                <span>D<?= number_format($fish['total_transport_cost'] ?? 0, 2) ?></span>
                                            </div>
                                            <div class="cost-breakdown-item">
                                                <span>Services:</span>
                                                <span>D<?= number_format($fish['total_services_cost'] ?? 0, 2) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5>Recent Employee Sales</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Customer</th>
                                <th>Items</th>
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
                                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                    <td><?= $sale['items_count'] ?></td>
                                    <td><?= number_format($sale['total_kg'], 2) ?></td>
                                    <td>D<?= number_format($sale['total_amount'], 2) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $sale['payment_method'])) ?></td>
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
                                <th>Profit %</th>
                                <th>Cost Breakdown</th>
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
                                    <td>D<?= number_format($order['running_cost'], 2) ?></td>
                                    <td class="<?= $order['profit'] >= 0 ? 'positive' : 'negative' ?>">
                                        D<?= number_format($order['profit'], 2) ?>
                                    </td>
                                    <td class="<?= $order['profit_percent'] >= 0 ? 'positive' : 'negative' ?>">
                                        <?= number_format($order['profit_percent'], 2) ?>%
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="toggleBreakdown(this)">Show Breakdown</button>
                                        <div class="cost-breakdown" style="display: none;">
                                            <div class="cost-breakdown-item">
                                                <span>Fingerlings:</span>
                                                <span>D<?= number_format($order['total_fingerlings_cost'], 2) ?></span>
                                            </div>
                                            <div class="cost-breakdown-item">
                                                <span>Feed:</span>
                                                <span>D<?= number_format($order['total_feed_cost'], 2) ?></span>
                                            </div>
                                            <div class="cost-breakdown-item">
                                                <span>Materials:</span>
                                                <span>D<?= number_format($order['total_material_cost'], 2) ?></span>
                                            </div>
                                            <div class="cost-breakdown-item">
                                                <span>Transport:</span>
                                                <span>D<?= number_format($order['total_transport_cost'], 2) ?></span>
                                            </div>
                                            <div class="cost-breakdown-item">
                                                <span>Services:</span>
                                                <span>D<?= number_format($order['total_services_cost'], 2) ?></span>
                                            </div>
                                        </div>
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
        // Toggle cost breakdown visibility
        function toggleBreakdown(button) {
            const breakdown = button.nextElementSibling;
            if (breakdown.style.display === 'none') {
                breakdown.style.display = 'block';
                button.textContent = 'Hide Breakdown';
            } else {
                breakdown.style.display = 'none';
                button.textContent = 'Show Breakdown';
            }
        }

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