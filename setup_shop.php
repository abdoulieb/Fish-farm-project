<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isEmployee() && !isAdmin()) {
    header("Location: index.php");
    exit();
}

// Check if shop already exists
$stmt = $pdo->prepare("SELECT * FROM shop_locations WHERE employee_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$existingShop = $stmt->fetch();

if ($existingShop) {
    header("Location: shop_management.php");
    exit();
}

// Get all locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationId = $_POST['id'] ?? '';
    $phoneNumber = $_POST['phone_number'] ?? '';
    $openingTime = $_POST['opening_time'] ?? '08:00';
    $closingTime = $_POST['closing_time'] ?? '17:00';
    $isOpen = isset($_POST['is_open']) ? 1 : 0;

    // Get location details
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->execute([$locationId]);
    $location = $stmt->fetch();

    if (!$location) {
        $error = "Invalid location selected";
    } else {
        try {
            // Insert new shop assignment
            $stmt = $pdo->prepare("
                INSERT INTO shop_locations 
                (employee_id,id, location_name, region, contact_phone, opening_time, closing_time, is_open)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $locationId,
                $location['name'],
                $location['address'], // Using address as region in this example
                $phoneNumber, // Correctly use the phone number input
                $openingTime,
                $closingTime,
                $isOpen
            ]);

            header("Location: shop_management.php?setup=success");
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .setup-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="setup-container">
            <h2 class="text-center mb-4"><i class="fas fa-store-alt me-2"></i>Set Up Your Shop</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form id="setupForm" method="POST">
                <div class="mb-3">
                    <label for="id" class="form-label">Select Location</label>
                    <select class="form-select" id="id" name="id" required>
                        <option value="">Choose a location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= $location['id'] ?>"><?= htmlspecialchars($location['name']) ?> - <?= htmlspecialchars($location['address']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="phone" class="form-control" id="phone_number" name="phone_number" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="opening_time" class="form-label">Opening Time</label>
                            <input type="time" class="form-control" id="opening_time" name="opening_time" value="08:00" required>
                        </div>

                        <div class="col-md-6">
                            <label for="closing_time" class="form-label">Closing Time</label>
                            <input type="time" class="form-control" id="closing_time" name="closing_time" value="17:00" required>
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_open" name="is_open" checked>
                        <label class="form-check-label" for="is_open">Shop is open</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Shop Details
                        </button>
                    </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>