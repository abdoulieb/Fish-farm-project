<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isEmployee() && !isAdmin()) {
    header("Location: index.php");
    exit();
}

// Get all locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

// Get current employee's shop location if exists
$currentShop = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM shop_locations WHERE employee_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentShop = $stmt->fetch();
}

// Handle AJAX request for updating hours
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_hours') {
        $openingTime = $_POST['opening_time'] ?? '';
        $closingTime = $_POST['closing_time'] ?? '';
        $isOpen = isset($_POST['is_open']) ? 1 : 0;

        try {
            if ($currentShop) {
                // Update existing shop hours
                $stmt = $pdo->prepare("
                    UPDATE shop_locations 
                    SET opening_time = ?, closing_time = ?, is_open = ?
                    WHERE employee_id = ?
                ");
                $stmt->execute([$openingTime, $closingTime, $isOpen, $_SESSION['user_id']]);

                echo json_encode(['success' => true, 'message' => 'Hours updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Shop location not set up yet']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    } elseif ($_POST['action'] === 'update_location') {
        $locationId = $_POST['location_id'] ?? '';

        try {
            // Get location details
            $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
            $stmt->execute([$locationId]);
            $location = $stmt->fetch();

            if (!$location) {
                echo json_encode(['success' => false, 'message' => 'Invalid location selected']);
                exit();
            }

            if ($currentShop) {
                // Update existing shop location
                $stmt = $pdo->prepare("
                    UPDATE shop_locations 
                    SET location_id = ?, location_name = ?, region = ?
                    WHERE employee_id = ?
                ");
                $stmt->execute([
                    $locationId,
                    $location['name'],
                    $location['address'],
                    $_SESSION['user_id']
                ]);
            } else {
                // Create new shop location
                $stmt = $pdo->prepare("
                    INSERT INTO shop_locations 
                    (employee_id, location_id, location_name, region, opening_time, closing_time, is_open)
                    VALUES (?, ?, ?, ?, '08:00', '17:00', 1)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $locationId,
                    $location['name'],
                    $location['address']
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Location updated successfully!']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .time-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .real-time-update {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Manage Your Shop Location</h2>

        <div class="real-time-update">
            <h4>Update Shop Location</h4>
            <form id="locationForm">
                <div class="mb-3">
                    <label for="location_id" class="form-label">Select Location</label>
                    <select class="form-select" id="location_id" name="location_id" required>
                        <option value="">Choose a location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= $location['id'] ?>" <?= ($currentShop && $currentShop['location_id'] == $location['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location['name']) ?> - <?= htmlspecialchars($location['address']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Location</button>
                <div id="locationUpdateStatus" class="mt-2"></div>
            </form>
        </div>

        <div class="real-time-update">
            <h4>Update Shop Hours</h4>
            <form id="hoursForm">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="opening_time" class="form-label">Opening Time</label>
                        <input type="time" class="form-control" id="opening_time" name="opening_time"
                            value="<?= htmlspecialchars($currentShop['opening_time'] ?? '08:00') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="closing_time" class="form-label">Closing Time</label>
                        <input type="time" class="form-control" id="closing_time" name="closing_time"
                            value="<?= htmlspecialchars($currentShop['closing_time'] ?? '17:00') ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_open" name="is_open"
                                <?= ($currentShop['is_open'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_open">Shop is open</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Hours</button>
                <div id="updateStatus" class="mt-2"></div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Current Shop Status</h5>
            </div>
            <div class="card-body">
                <?php if ($currentShop): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Location Information</h6>
                            <p><strong>Name:</strong> <?= htmlspecialchars($currentShop['location_name']) ?></p>
                            <p><strong>Region:</strong> <?= htmlspecialchars($currentShop['region']) ?></p>
                            <p><strong>Contact:</strong> <?= htmlspecialchars($currentShop['contact_phone']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Operating Hours</h6>
                            <p><strong>Opening Time:</strong> <span id="currentOpening"><?= date('g:i A', strtotime($currentShop['opening_time'])) ?></span></p>
                            <p><strong>Closing Time:</strong> <span id="currentClosing"><?= date('g:i A', strtotime($currentShop['closing_time'])) ?></span></p>
                            <p><strong>Status:</strong>
                                <span class="badge <?= $currentShop['is_open'] ? 'bg-success' : 'bg-danger' ?>" id="currentStatus">
                                    <?= $currentShop['is_open'] ? 'Open' : 'Closed' ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">You haven't set up your shop location yet.</div>
                    <a href="setup_shop.php" class="btn btn-primary">Set Up Your Shop</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <d class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Shop Status Report</h5>
        </div>
        <div class="card-body">
            <?php
            try {
                $stmt = $pdo->query("SELECT location_name, region, opening_time, closing_time, is_open FROM shop_locations");
                $shops = $stmt->fetchAll();

                if ($shops): ?>
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Shop Name</th>
                                <th>Region</th>
                                <th>Opening Time</th>
                                <th>Closing Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shops as $shop): ?>
                                <tr>
                                    <td><?= htmlspecialchars($shop['location_name']) ?></td>
                                    <td><?= htmlspecialchars($shop['region']) ?></td>
                                    <td><?= date('g:i A', strtotime($shop['opening_time'])) ?></td>
                                    <td><?= date('g:i A', strtotime($shop['closing_time'])) ?></td>
                                    <td>
                                        <span class="badge <?= $shop['is_open'] ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $shop['is_open'] ? 'Open' : 'Closed' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-warning">No shop data available.</div>
            <?php endif;
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error fetching shop data: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.getElementById('hoursForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                formData.append('action', 'update_hours');

                const statusDiv = document.getElementById('updateStatus');
                statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Updating...';
                statusDiv.className = 'mt-2 text-info';

                fetch('shop_management.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusDiv.innerHTML = '<span class="text-success">✓ ' + data.message + '</span>';

                            // Update the displayed values
                            const openingTime = document.getElementById('opening_time').value;
                            const closingTime = document.getElementById('closing_time').value;
                            const isOpen = document.getElementById('is_open').checked;

                            document.getElementById('currentOpening').textContent = formatTime(openingTime);
                            document.getElementById('currentClosing').textContent = formatTime(closingTime);

                            const statusBadge = document.getElementById('currentStatus');
                            statusBadge.textContent = isOpen ? 'Open' : 'Closed';
                            statusBadge.className = isOpen ? 'badge bg-success' : 'badge bg-danger';

                            // Hide success message after 3 seconds
                            setTimeout(() => {
                                statusDiv.innerHTML = '';
                            }, 3000);
                        } else {
                            statusDiv.innerHTML = '<span class="text-danger">✗ ' + data.message + '</span>';
                        }
                    })
                    .catch(error => {
                        statusDiv.innerHTML = '<span class="text-danger">Error: ' + error + '</span>';
                    });
            });

            function formatTime(timeString) {
                const [hours, minutes] = timeString.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const displayHour = hour % 12 || 12;
                return `${displayHour}:${minutes} ${ampm}`;
            }
        </script>
</body>

</html>