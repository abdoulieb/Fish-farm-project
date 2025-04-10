<?php
require_once 'auth.php';
require_once 'functions.php';

// Get available inventory for employee's location
$employeeInventory = [];
if (isEmployee()) {
    $stmt = $pdo->prepare("
        SELECT ft.name, li.quantity 
        FROM location_inventory li
        JOIN fish_types ft ON li.fish_type_id = ft.id
        WHERE li.employee_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $employeeInventory = $stmt->fetchAll();
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">Fish Farm</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="index.php">
                        Home
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>" href="orders.php">My Orders</a>
                    </li>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : '' ?>" href="admin.php">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'cost_management.php' ? 'active' : '' ?>" href="cost_management.php">Cost Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profit_analysis.php' ? 'active' : '' ?>" href="profit_analysis.php">Profit Analysis</a>
                    </li>
                <?php endif; ?>

                <?php if (isEmployee()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'employee_sales.php' ? 'active' : '' ?>" href="employee_sales.php">
                            Sales
                        </a>
                    </li>

                    <?php if (canEmployeeProcessOrders() || isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'process_orders.php' ? 'active' : '' ?>" href="process_orders.php">Process Orders</a>
                        </li>
                    <?php endif; ?>

                    <?php if (canEmployeeRecordFatality() || isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'fatality_records.php' ? 'active' : '' ?>" href="fatality_records.php">Record Fatality</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : '' ?>" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'active' : '' ?>" href="register.php">Register</a>
                    </li>
                <?php endif; ?>

                <?php
                // Add near the top of navbar.php (after session_start())
                $pendingAssignments = 0;
                if (isset($_SESSION['user_id']) && canEmployeeSell()) {
                    $pendingAssignments = getPendingAssignmentsCount($_SESSION['user_id']);
                }
                ?>

                <!-- In the navbar HTML, add this to the employee dropdown or main menu: -->
                <li class="nav-item">
                    <a class="nav-link" href="location_management.php">
                        <i class="fas fa-boxes"></i> Inventory
                        <?php if ($pendingAssignments > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $pendingAssignments ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php if (isEmployee() && !empty($employeeInventory) && basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
    <div class="container mt-3">
        <div class="alert alert-info">
            <h5>Your Available Inventory</h5>
            <ul class="mb-0">
                <?php foreach ($employeeInventory as $item): ?>
                    <li><?= htmlspecialchars($item['name']) ?>: <?= number_format($item['quantity'], 2) ?> kg</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>