<?php
require_once 'auth.php';
require_once 'functions.php';

// Check if user is employee with fatality recording permission
if (!canEmployeeRecordFatality() && !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to record fatalities";
    header("Location: index.php");
    exit();
}

// Rest of your existing code...

$fishTypes = getAllFishTypes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fishTypeId = $_POST['fish_type_id'];
    $quantity = intval($_POST['quantity']);
    $date = $_POST['date'];
    $cause = $_POST['cause'] ?? '';
    $notes = $_POST['notes'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO fish_fatalities (fish_type_id, quantity, date_recorded, cause, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fishTypeId, $quantity, $date, $cause, $notes, $_SESSION['user_id']]);

        // Update inventory
        $stmt = $pdo->prepare("UPDATE inventory SET quantity_kg = quantity_kg - ? WHERE fish_type_id = ?");
        $stmt->execute([$quantity, $fishTypeId]);

        $_SESSION['message'] = "Fatality recorded successfully!";
        header("Location: fatality_records.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error recording fatality: " . $e->getMessage();
    }
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Fatality - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Record Fish Fatality</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="fish_type_id" class="form-label">Fish Type</label>
                        <select class="form-control" id="fish_type_id" name="fish_type_id" required>
                            <option value="">Select Fish Type</option>
                            <?php foreach ($fishTypes as $fish): ?>
                                <option value="<?= $fish['id'] ?>"><?= htmlspecialchars($fish['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity (number of fish)</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="cause" class="form-label">Cause (optional)</label>
                        <select class="form-control" id="cause" name="cause">
                            <option value="">Unknown</option>
                            <option value="disease">Disease</option>
                            <option value="water_quality">Water Quality</option>
                            <option value="predation">Predation</option>
                            <option value="handling">Handling</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Record Fatality</button>
                <a href="admin.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>

        <div class="mt-5">
            <h4>Recent Fatalities</h4>
            <?php
            $stmt = $pdo->query("
                SELECT f.*, ft.name as fish_name, u.username as recorded_by 
                FROM fish_fatalities f
                JOIN fish_types ft ON f.fish_type_id = ft.id
                JOIN users u ON f.recorded_by = u.id
                ORDER BY f.date_recorded DESC, f.recorded_at DESC
                LIMIT 10
            ");
            $fatalities = $stmt->fetchAll();

            if (empty($fatalities)): ?>
                <p>No fatalities recorded yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Fish Type</th>
                                <th>Quantity</th>
                                <th>Cause</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fatalities as $fatality): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fatality['date_recorded']) ?></td>
                                    <td><?= htmlspecialchars($fatality['fish_name']) ?></td>
                                    <td><?= $fatality['quantity'] ?></td>
                                    <td><?= htmlspecialchars($fatality['cause'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($fatality['recorded_by']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>