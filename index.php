<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$fishTypes = getAllFishTypes();
$inventory = getInventory();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fish Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Fish Farm</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">My Orders</a>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Admin</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Available Fish</h2>
        <div class="row">
            <?php foreach ($fishTypes as $fish): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($fish['name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($fish['description']) ?></p>
                            <p class="card-text"><strong>Price:</strong> D<?= number_format($fish['price_per_kg'], 2) ?> per kg</p>
                            <p class="card-text">
                                <strong>Available:</strong>
                                <?php
                                $available = 0;
                                foreach ($inventory as $item) {
                                    if ($item['fish_type_id'] == $fish['id']) {
                                        $available = $item['quantity_kg'];
                                        break;
                                    }
                                }
                                echo number_format($available, 2) . ' kg';
                                ?>
                            </p>
                            <?php if ($available > 0): ?>
                                <a href="order.php?fish_id=<?= $fish['id'] ?>" class="btn btn-primary">Order Now</a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>