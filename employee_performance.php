<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Default time period (last 30 days)
$startDate = date('Y-m-d', strtotime('-30 days'));
$endDate = date('Y-m-d');

// Handle form submission for filtering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'] ?? $startDate;
    $endDate = $_POST['end_date'] ?? $endDate;
    $locationFilter = isset($_POST['location_id']) ? intval($_POST['location_id']) : null;
}

// Get all shop locations for filter dropdown
$locations = $pdo->query("SELECT id, location_name as name FROM shop_locations GROUP BY id, location_name ORDER BY location_name")->fetchAll();

// Get employee performance data
$performanceQuery = "
    SELECT 
        u.id as employee_id,
        u.username as employee_name,
        sl.location_name,
        sl.id as location_id,
        COUNT(s.id) as total_sales,
        COALESCE(SUM(s.total_amount), 0) as total_revenue,
        COALESCE(SUM(si.quantity_kg), 0) as total_kg_sold,
        COALESCE(AVG(s.total_amount), 0) as avg_sale_amount,
        COUNT(DISTINCT DATE(s.sale_date)) as days_worked
    FROM 
        users u
    LEFT JOIN 
        shop_locations sl ON u.id = sl.employee_id
    LEFT JOIN 
        sales s ON (u.id = s.employee_id AND s.sale_date BETWEEN ? AND ?)
    LEFT JOIN 
        sale_items si ON s.id = si.sale_id
    WHERE 
        u.role = 'employee'
";

// Add location filter if specified
if (isset($locationFilter) && $locationFilter > 0) {
    $performanceQuery .= " AND sl.id = ?";
    $stmt = $pdo->prepare($performanceQuery);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $locationFilter]);
} else {
    $performanceQuery .= " GROUP BY u.id, sl.id";
    $stmt = $pdo->prepare($performanceQuery);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
}

$performanceData = $stmt->fetchAll();

// Prepare data for charts
$chartLabels = [];
$salesData = [];
$revenueData = [];
$kgData = [];
$avgSaleData = [];
$efficiencyData = [];

foreach ($performanceData as $employee) {
    $label = $employee['employee_name'];
    if (!empty($employee['location_name'])) {
        $label .= ' (' . $employee['location_name'] . ')';
    } else {
        $label .= ' (No location assigned)';
    }
    $chartLabels[] = $label;
    $salesData[] = $employee['total_sales'];
    $revenueData[] = $employee['total_revenue'];
    $kgData[] = $employee['total_kg_sold'];
    $avgSaleData[] = $employee['avg_sale_amount'];

    // Calculate efficiency (revenue per day worked)
    $efficiency = $employee['days_worked'] > 0 ? $employee['total_revenue'] / $employee['days_worked'] : 0;
    $efficiencyData[] = round($efficiency, 2);
}
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Performance Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }

        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .filter-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Employee Performance Analysis</h2>

        <div class="filter-form">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                        value="<?= htmlspecialchars($startDate) ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                        value="<?= htmlspecialchars($endDate) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="location_id" class="form-label">Filter by Location</label>
                    <select class="form-select" id="location_id" name="location_id">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= $location['id'] ?>"
                                <?= isset($locationFilter) && $locationFilter == $location['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>

        <?php if (empty($performanceData)): ?>
            <div class="alert alert-info">No performance data found for the selected period.</div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Total Sales by Employee
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Total Revenue by Employee (D)
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Total Kg Sold by Employee
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="kgChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Average Sale Amount by Employee (D)
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="avgSaleChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            Revenue Efficiency (Revenue per Day Worked)
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="efficiencyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Revenue Distribution by Employee
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="revenueDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Sales Distribution by Employee
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
            <div class="card">
                <div class="card-header">
                    Detailed Performance Data
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Location</th>
                                    <th>Total Sales</th>
                                    <th>Total Revenue (D)</th>
                                    <th>Total Kg Sold</th>
                                    <th>Avg Sale (D)</th>
                                    <th>Days Worked</th>
                                    <th>Revenue/Day (D)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performanceData as $employee): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($employee['employee_name']) ?></td>
                                        <td><?= !empty($employee['location_name']) ? htmlspecialchars($employee['location_name']) : 'Not assigned' ?></td>
                                        <td><?= $employee['total_sales'] ?></td>
                                        <td>D<?= number_format($employee['total_revenue'], 2) ?></td>
                                        <td><?= number_format($employee['total_kg_sold'], 2) ?></td>
                                        <td>D<?= number_format($employee['avg_sale_amount'], 2) ?></td>
                                        <td><?= $employee['days_worked'] ?></td>
                                        <td>D<?= number_format($employee['days_worked'] > 0 ? $employee['total_revenue'] / $employee['days_worked'] : 0, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($performanceData)): ?>
                // Sales Chart
                const salesCtx = document.getElementById('salesChart').getContext('2d');
                const salesChart = new Chart(salesCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($chartLabels) ?>,
                        datasets: [{
                            label: 'Number of Sales',
                            data: <?= json_encode($salesData) ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Sales'
                                }
                            }
                        }
                    }
                });

                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                const revenueChart = new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($chartLabels) ?>,
                        datasets: [{
                            label: 'Total Revenue (D)',
                            data: <?= json_encode($revenueData) ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Total Revenue (D)'
                                }
                            }
                        }
                    }
                });

                // Kg Sold Chart
                const kgCtx = document.getElementById('kgChart').getContext('2d');
                const kgChart = new Chart(kgCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($chartLabels) ?>,
                        datasets: [{
                            label: 'Kg Sold',
                            data: <?= json_encode($kgData) ?>,
                            backgroundColor: 'rgba(255, 159, 64, 0.7)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Kilograms Sold'
                                }
                            }
                        }
                    }
                });

                // Average Sale Chart
                const avgSaleCtx = document.getElementById('avgSaleChart').getContext('2d');
                const avgSaleChart = new Chart(avgSaleCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($chartLabels) ?>,
                        datasets: [{
                            label: 'Average Sale Amount (D)',
                            data: <?= json_encode($avgSaleData) ?>,
                            backgroundColor: 'rgba(153, 102, 255, 0.7)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Average Sale Amount (D)'
                                }
                            }
                        }
                    }
                });

                // Efficiency Chart
                const efficiencyCtx = document.getElementById('efficiencyChart').getContext('2d');
                const efficiencyChart = new Chart(efficiencyCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($chartLabels) ?>,
                        datasets: [{
                            label: 'Revenue per Day Worked (D)',
                            data: <?= json_encode($efficiencyData) ?>,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Revenue per Day Worked (D)'
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });
        // Revenue Distribution Pie Chart
const revenueDistributionCtx = document.getElementById('revenueDistributionChart').getContext('2d');
const revenueDistributionChart = new Chart(revenueDistributionCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            data: <?= json_encode($revenueData) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(199, 199, 199, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(199, 199, 199, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${label}: D${value.toFixed(2)} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Sales Distribution Pie Chart
const salesDistributionCtx = document.getElementById('salesDistributionChart').getContext('2d');
const salesDistributionChart = new Chart(salesDistributionCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            data: <?= json_encode($salesData) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(199, 199, 199, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(199, 199, 199, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${label}: ${value} sales (${percentage}%)`;
                    }
                }
            }
        }
    }
});
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>