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
    <style>
        .fish-card {
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .fish-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .fish-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .fish-card .card-body p {
            flex: 1;
        }

        .fish-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .fish-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            text-align: center;
            padding: 20px 0;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Fish Farm</a>
            <?php if (isEmployee() || isadmin()): ?>
                <a class="navbar-brand" href="employee_sales.php">Sale</a>
            <?php endif; ?>
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
                    <?php if (canEmployeeRecordFatality() || isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'fatality_records.php' ? 'active' : '' ?>" href="fatality_records.php">Record Fatality</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin_dashboard.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-cog"></i>Image management Panel
                        </a>
                    <?php else: ?>

                    <?php endif; ?>
                </div>

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
                    <div class="card fish-card">
                        <!-- Add image display here -->
                        <?php if (!empty($fish['image_path'])): ?>
                            <img src="<?= htmlspecialchars($fish['image_path']) ?>" class="fish-img" alt="<?= htmlspecialchars($fish['name']) ?>">
                        <?php else: ?>
                            <div class="fish-icon">
                                <i class="fas fa-fish"></i>
                            </div>
                        <?php endif; ?>
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
                                <a href="order.php?fish_id=<?= $fish['id'] ?>" class="btn btn-primary mt-auto">Order Now</a>
                            <?php else: ?>
                                <button class="btn btn-secondary mt-auto" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add Font Awesome for the fish icon -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>

</html>