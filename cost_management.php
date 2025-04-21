<?php
require_once 'navbar.php';
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an update or new record
    if (isset($_POST['id'])) {
        // Update existing record
        $id = $_POST['id'];
        $fishTypeId = $_POST['fish_type_id'];
        $date = $_POST['date'];

        // Prepare the data array for update
        $data = [
            'id' => $id,
            'fish_type_id' => $fishTypeId,
            'date_recorded' => $date,

            // Fingerlings Cost
            'fingerlings_quantity' => $_POST['fingerlings_quantity'] ?? null,
            'fingerlings_unit_price' => $_POST['fingerlings_unit_price'] ?? null,
            'fingerlings_total_cost' => $_POST['fingerlings_total_cost'] ?? null,

            // Feed Cost
            'starter_feed_quantity' => $_POST['starter_feed_quantity'] ?? null,
            'starter_feed_unit_price' => $_POST['starter_feed_unit_price'] ?? null,
            'starter_feed_total_cost' => $_POST['starter_feed_total_cost'] ?? null,
            'grower_feed_quantity' => $_POST['grower_feed_quantity'] ?? null,
            'grower_feed_unit_price' => $_POST['grower_feed_unit_price'] ?? null,
            'grower_feed_total_cost' => $_POST['grower_feed_total_cost'] ?? null,

            // Material Cost
            'basin_quantity' => $_POST['basin_quantity'] ?? null,
            'basin_unit_price' => $_POST['basin_unit_price'] ?? null,
            'basin_total_cost' => $_POST['basin_total_cost'] ?? null,
            'fish_nets_quantity' => $_POST['fish_nets_quantity'] ?? null,
            'fish_nets_unit_price' => $_POST['fish_nets_unit_price'] ?? null,
            'fish_nets_total_cost' => $_POST['fish_nets_total_cost'] ?? null,
            'water_quality_meter_quantity' => $_POST['water_quality_meter_quantity'] ?? null,
            'water_quality_meter_unit_price' => $_POST['water_quality_meter_unit_price'] ?? null,
            'water_quality_meter_total_cost' => $_POST['water_quality_meter_total_cost'] ?? null,
            'pond_pumps_quantity' => $_POST['pond_pumps_quantity'] ?? null,
            'pond_pumps_unit_price' => $_POST['pond_pumps_unit_price'] ?? null,
            'pond_pumps_total_cost' => $_POST['pond_pumps_total_cost'] ?? null,
            'pond_aeration_quantity' => $_POST['pond_aeration_quantity'] ?? null,
            'pond_aeration_unit_price' => $_POST['pond_aeration_unit_price'] ?? null,
            'pond_aeration_total_cost' => $_POST['pond_aeration_total_cost'] ?? null,
            'pond_vacuum_quantity' => $_POST['pond_vacuum_quantity'] ?? null,
            'pond_vacuum_unit_price' => $_POST['pond_vacuum_unit_price'] ?? null,
            'pond_vacuum_total_cost' => $_POST['pond_vacuum_total_cost'] ?? null,
            'fencing_quantity' => $_POST['fencing_quantity'] ?? null,
            'fencing_unit_price' => $_POST['fencing_unit_price'] ?? null,
            'fencing_total_cost' => $_POST['fencing_total_cost'] ?? null,
            'smoker_ovon_unit_price' => $_POST['smoker_ovon_unit_price'] ?? null,
            'smoker_ovon_total_cost' => $_POST['smoker_ovon_total_cost'] ?? null,

            // Transportation Cost
            'transport_senegal_quantity' => $_POST['transport_senegal_quantity'] ?? null,
            'transport_senegal_unit_price' => $_POST['transport_senegal_unit_price'] ?? null,
            'transport_senegal_total_cost' => $_POST['transport_senegal_total_cost'] ?? null,
            'transport_gambia_quantity' => $_POST['transport_gambia_quantity'] ?? null,
            'transport_gambia_unit_price' => $_POST['transport_gambia_unit_price'] ?? null,
            'transport_gambia_total_cost' => $_POST['transport_gambia_total_cost'] ?? null,

            // Services/Indirect Cost
            'water_quantity' => $_POST['water_quantity'] ?? null,
            'water_unit_price' => $_POST['water_unit_price'] ?? null,
            'water_total_cost' => $_POST['water_total_cost'] ?? null,
            'electricity_quantity' => $_POST['electricity_quantity'] ?? null,
            'electricity_unit_price' => $_POST['electricity_unit_price'] ?? null,
            'electricity_total_cost' => $_POST['electricity_total_cost'] ?? null,
            'maintenance_cost' => $_POST['maintenance_cost'] ?? null,
            'rent_cost' => $_POST['rent_cost'] ?? null,
            'refrigeration_cost' => $_POST['refrigeration_cost'] ?? null,
            'marketing_cost' => $_POST['marketing_cost'] ?? null,
            'medication_cost' => $_POST['medication_cost'] ?? null,
            'hosting_cost' => $_POST['hosting_cost'] ?? null,
            'electrical_installation_cost' => $_POST['electrical_installation_cost'] ?? null,
            'business_registration_cost' => $_POST['business_registration_cost'] ?? null,
            'insurance_cost' => $_POST['insurance_cost'] ?? null,
            'tax_cost' => $_POST['tax_cost'] ?? null,
        ];

        // Calculate totals
        $data['total_fingerlings_cost'] = $data['fingerlings_total_cost'] ?? 0;
        $data['total_feed_cost'] = (float)($data['starter_feed_total_cost'] ?? 0) + (float)($data['grower_feed_total_cost'] ?? 0);
        $data['total_material_cost'] = (float)($data['basin_total_cost'] ?? 0) + (float)($data['fish_nets_total_cost'] ?? 0) +
            (float)($data['water_quality_meter_total_cost'] ?? 0) + (float)($data['pond_pumps_total_cost'] ?? 0) +
            (float)($data['pond_aeration_total_cost'] ?? 0) + (float)($data['pond_vacuum_total_cost'] ?? 0) +
            (float)($data['fencing_total_cost'] ?? 0);
        $data['total_transport_cost'] = (float)($data['transport_senegal_total_cost'] ?? 0) + (float)($data['transport_gambia_total_cost'] ?? 0);
        $data['total_services_cost'] = (float)($data['water_total_cost'] ?? 0) + (float)($data['electricity_total_cost'] ?? 0) +
            (float)($data['maintenance_cost'] ?? 0) + (float)($data['rent_cost'] ?? 0) +
            (float)($data['refrigeration_cost'] ?? 0) + (float)($data['marketing_cost'] ?? 0) +
            (float)($data['medication_cost'] ?? 0) + (float)($data['hosting_cost'] ?? 0) +
            (float)($data['electrical_installation_cost'] ?? 0) + (float)($data['business_registration_cost'] ?? 0) +
            (float)($data['insurance_cost'] ?? 0) + (float)($data['tax_cost'] ?? 0);
        $data['running_cost'] = $data['total_fingerlings_cost'] + $data['total_feed_cost'] + $data['total_material_cost'] +
            $data['total_transport_cost'] + $data['total_services_cost'];

        try {
            // Prepare the SQL statement for update
            $setClause = [];
            foreach ($data as $key => $value) {
                if ($key !== 'id') {
                    $setClause[] = "$key = :$key";
                }
            }
            $sql = "UPDATE detailed_costs SET " . implode(", ", $setClause) . " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            $_SESSION['message'] = "Cost record updated successfully!";
            header("Location: cost_management.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating cost record: " . $e->getMessage();
            header("Location: cost_management.php");
            exit();
        }
    } else {
        // Handle new record insertion
        $fishTypeId = $_POST['fish_type_id'];
        $date = $_POST['date'];

        // Prepare the data array for insertion
        $data = [
            'fish_type_id' => $fishTypeId,
            'date_recorded' => $date,

            // Fingerlings Cost
            'fingerlings_quantity' => $_POST['fingerlings_quantity'] ?? null,
            'fingerlings_unit_price' => $_POST['fingerlings_unit_price'] ?? null,
            'fingerlings_total_cost' => $_POST['fingerlings_total_cost'] ?? null,

            // Feed Cost
            'starter_feed_quantity' => $_POST['starter_feed_quantity'] ?? null,
            'starter_feed_unit_price' => $_POST['starter_feed_unit_price'] ?? null,
            'starter_feed_total_cost' => $_POST['starter_feed_total_cost'] ?? null,
            'grower_feed_quantity' => $_POST['grower_feed_quantity'] ?? null,
            'grower_feed_unit_price' => $_POST['grower_feed_unit_price'] ?? null,
            'grower_feed_total_cost' => $_POST['grower_feed_total_cost'] ?? null,

            // Material Cost
            'basin_quantity' => $_POST['basin_quantity'] ?? null,
            'basin_unit_price' => $_POST['basin_unit_price'] ?? null,
            'basin_total_cost' => $_POST['basin_total_cost'] ?? null,
            'fish_nets_quantity' => $_POST['fish_nets_quantity'] ?? null,
            'fish_nets_unit_price' => $_POST['fish_nets_unit_price'] ?? null,
            'fish_nets_total_cost' => $_POST['fish_nets_total_cost'] ?? null,
            'water_quality_meter_quantity' => $_POST['water_quality_meter_quantity'] ?? null,
            'water_quality_meter_unit_price' => $_POST['water_quality_meter_unit_price'] ?? null,
            'water_quality_meter_total_cost' => $_POST['water_quality_meter_total_cost'] ?? null,
            'pond_pumps_quantity' => $_POST['pond_pumps_quantity'] ?? null,
            'pond_pumps_unit_price' => $_POST['pond_pumps_unit_price'] ?? null,
            'pond_pumps_total_cost' => $_POST['pond_pumps_total_cost'] ?? null,
            'pond_aeration_quantity' => $_POST['pond_aeration_quantity'] ?? null,
            'pond_aeration_unit_price' => $_POST['pond_aeration_unit_price'] ?? null,
            'pond_aeration_total_cost' => $_POST['pond_aeration_total_cost'] ?? null,
            'pond_vacuum_quantity' => $_POST['pond_vacuum_quantity'] ?? null,
            'pond_vacuum_unit_price' => $_POST['pond_vacuum_unit_price'] ?? null,
            'pond_vacuum_total_cost' => $_POST['pond_vacuum_total_cost'] ?? null,
            'fencing_quantity' => $_POST['fencing_quantity'] ?? null,
            'fencing_unit_price' => $_POST['fencing_unit_price'] ?? null,
            'fencing_total_cost' => $_POST['fencing_total_cost'] ?? null,
            'smoker_ovon_unit_price' => $_POST['smoker_ovon_unit_price'] ?? null,
            'smoker_ovon_total_cost' => $_POST['smoker_ovon_total_cost'] ?? null,

            // Transportation Cost
            'transport_senegal_quantity' => $_POST['transport_senegal_quantity'] ?? null,
            'transport_senegal_unit_price' => $_POST['transport_senegal_unit_price'] ?? null,
            'transport_senegal_total_cost' => $_POST['transport_senegal_total_cost'] ?? null,
            'transport_gambia_quantity' => $_POST['transport_gambia_quantity'] ?? null,
            'transport_gambia_unit_price' => $_POST['transport_gambia_unit_price'] ?? null,
            'transport_gambia_total_cost' => $_POST['transport_gambia_total_cost'] ?? null,

            // Services/Indirect Cost
            'water_quantity' => $_POST['water_quantity'] ?? null,
            'water_unit_price' => $_POST['water_unit_price'] ?? null,
            'water_total_cost' => $_POST['water_total_cost'] ?? null,
            'electricity_quantity' => $_POST['electricity_quantity'] ?? null,
            'electricity_unit_price' => $_POST['electricity_unit_price'] ?? null,
            'electricity_total_cost' => $_POST['electricity_total_cost'] ?? null,
            'maintenance_cost' => $_POST['maintenance_cost'] ?? null,
            'rent_cost' => $_POST['rent_cost'] ?? null,
            'refrigeration_cost' => $_POST['refrigeration_cost'] ?? null,
            'marketing_cost' => $_POST['marketing_cost'] ?? null,
            'medication_cost' => $_POST['medication_cost'] ?? null,
            'hosting_cost' => $_POST['hosting_cost'] ?? null,
            'electrical_installation_cost' => $_POST['electrical_installation_cost'] ?? null,
            'business_registration_cost' => $_POST['business_registration_cost'] ?? null,
            'insurance_cost' => $_POST['insurance_cost'] ?? null,
            'tax_cost' => $_POST['tax_cost'] ?? null,
        ];

        // Calculate totals
        $data['total_fingerlings_cost'] = $data['fingerlings_total_cost'] ?? 0;
        $data['total_feed_cost'] = (float)($data['starter_feed_total_cost'] ?? 0) + (float)($data['grower_feed_total_cost'] ?? 0);
        $data['total_material_cost'] = (float)($data['basin_total_cost'] ?? 0) + (float)($data['fish_nets_total_cost'] ?? 0) +
            (float)($data['water_quality_meter_total_cost'] ?? 0) + (float)($data['pond_pumps_total_cost'] ?? 0) +
            (float)($data['pond_aeration_total_cost'] ?? 0) + (float)($data['pond_vacuum_total_cost'] ?? 0) +
            (float)($data['fencing_total_cost'] ?? 0);
        $data['total_transport_cost'] = (float)($data['transport_senegal_total_cost'] ?? 0) + (float)($data['transport_gambia_total_cost'] ?? 0);
        $data['total_services_cost'] = (float)($data['water_total_cost'] ?? 0) + (float)($data['electricity_total_cost'] ?? 0) +
            (float)($data['maintenance_cost'] ?? 0) + (float)($data['rent_cost'] ?? 0) +
            (float)($data['refrigeration_cost'] ?? 0) + (float)($data['marketing_cost'] ?? 0) +
            (float)($data['medication_cost'] ?? 0) + (float)($data['hosting_cost'] ?? 0) +
            (float)($data['electrical_installation_cost'] ?? 0) + (float)($data['business_registration_cost'] ?? 0) +
            (float)($data['insurance_cost'] ?? 0) + (float)($data['tax_cost'] ?? 0);
        $data['running_cost'] = $data['total_fingerlings_cost'] + $data['total_feed_cost'] + $data['total_material_cost'] +
            $data['total_transport_cost'] + $data['total_services_cost'];

        try {
            // Prepare the SQL statement for insertion
            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $sql = "INSERT INTO detailed_costs ($columns) VALUES ($placeholders)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            $_SESSION['message'] = "New cost record added successfully!";
            header("Location: cost_management.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding new cost record: " . $e->getMessage();
            header("Location: cost_management.php");
            exit();
        }
    }
}

// Get all fish types
$fishTypes = getAllFishTypes();

// Get cost history
$stmt = $pdo->query("
    SELECT dc.*, ft.name as fish_name
    FROM detailed_costs dc
    JOIN fish_types ft ON dc.fish_type_id = ft.id
    ORDER BY date_recorded DESC, fish_type_id
");
$costHistory = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cost Management - Fish Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cost-section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .cost-section-header {
            background-color: rgba(248, 249, 250, 0.31);
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #ddd;
        }

        .cost-section-content {
            padding: 15px;
            display: none;
        }

        .cost-section.active .cost-section-content {
            display: block;
        }

        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .modal-lg {
            max-width: 90%;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Production Cost Management</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Add New Cost Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Add New Cost Record</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fish_type_id">Fish Type</label>
                                <select class="form-control" id="fish_type_id" name="fish_type_id" required>
                                    <?php foreach ($fishTypes as $fish): ?>
                                        <option value="<?= $fish['id'] ?>"><?= htmlspecialchars($fish['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Fingerlings Cost Section -->
                    <div class="cost-section">
                        <div class="cost-section-header" onclick="toggleSection(this)">
                            <h5>Fingerlings Cost</h5>
                        </div>
                        <div class="cost-section-content">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fingerlings_quantity">Quantity</label>
                                        <input type="number" class="form-control" id="fingerlings_quantity" name="fingerlings_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fingerlings_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="fingerlings_unit_price" name="fingerlings_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fingerlings_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="fingerlings_total_cost" name="fingerlings_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feed Cost Section -->
                    <div class="cost-section">
                        <div class="cost-section-header" onclick="toggleSection(this)">
                            <h5>Feed Cost</h5>
                        </div>
                        <div class="cost-section-content">
                            <h6>Starter Feed</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="starter_feed_quantity">Quantity (kg)</label>
                                        <input type="number" class="form-control" id="starter_feed_quantity" name="starter_feed_quantity" step="0.1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="starter_feed_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="starter_feed_unit_price" name="starter_feed_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="starter_feed_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="starter_feed_total_cost" name="starter_feed_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <h6>Grower Feed</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="grower_feed_quantity">Quantity (kg)</label>
                                        <input type="number" class="form-control" id="grower_feed_quantity" name="grower_feed_quantity" step="0.1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="grower_feed_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="grower_feed_unit_price" name="grower_feed_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="grower_feed_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="grower_feed_total_cost" name="grower_feed_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Material Cost Section -->
                    <div class="cost-section">
                        <div class="cost-section-header" onclick="toggleSection(this)">
                            <h5>Material Cost</h5>
                        </div>
                        <div class="cost-section-content">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="basin_quantity">Basin 25m3 (Quantity)</label>
                                        <input type="number" class="form-control" id="basin_quantity" name="basin_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="basin_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="basin_unit_price" name="basin_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="basin_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="basin_total_cost" name="basin_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Fish Nets -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fish_nets_quantity">Fish Nets (Quantity)</label>
                                        <input type="number" class="form-control" id="fish_nets_quantity" name="fish_nets_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fish_nets_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="fish_nets_unit_price" name="fish_nets_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fish_nets_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="fish_nets_total_cost" name="fish_nets_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Water Quality Meter -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="water_quality_meter_quantity">Water Quality Meter (Quantity)</label>
                                        <input type="number" class="form-control" id="water_quality_meter_quantity" name="water_quality_meter_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="water_quality_meter_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="water_quality_meter_unit_price" name="water_quality_meter_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="water_quality_meter_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="water_quality_meter_total_cost" name="water_quality_meter_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Pond Pumps -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_pumps_quantity">Pond Pumps (Quantity)</label>
                                        <input type="number" class="form-control" id="pond_pumps_quantity" name="pond_pumps_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_pumps_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="pond_pumps_unit_price" name="pond_pumps_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_pumps_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="pond_pumps_total_cost" name="pond_pumps_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Pond Aeration -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_aeration_quantity">Pond Aeration (Quantity)</label>
                                        <input type="number" class="form-control" id="pond_aeration_quantity" name="pond_aeration_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_aeration_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="pond_aeration_unit_price" name="pond_aeration_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_aeration_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="pond_aeration_total_cost" name="pond_aeration_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Pond Vacuum -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_vacuum_quantity">Pond Vacuum (Quantity)</label>
                                        <input type="number" class="form-control" id="pond_vacuum_quantity" name="pond_vacuum_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_vacuum_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="pond_vacuum_unit_price" name="pond_vacuum_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pond_vacuum_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="pond_vacuum_total_cost" name="pond_vacuum_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Fencing -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fencing_quantity">Fencing (Quantity)</label>
                                        <input type="number" class="form-control" id="fencing_quantity" name="fencing_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fencing_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="fencing_unit_price" name="fencing_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fencing_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="fencing_total_cost" name="fencing_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Smoker Ovon -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="smoker_ovon_quantity">Smoker oven quantity</label>
                                        <input type="number" class="form-control" id="smoker_ovon_quantity" name="smoker_ovon_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="smoker_ovon_unit_price">Smoker Ovon Unit Price (D)</label>
                                        <input type="number" class="form-control" id="smoker_ovon_unit_price" name="smoker_ovon_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="smoker_ovon_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="smoker_ovon_total_cost" name="smoker_ovon_total_cost" step="0.01" min="0">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Transportation Cost Section -->
                    <div class="cost-section">
                        <div class="cost-section-header" onclick="toggleSection(this)">
                            <h5>Transportation Cost</h5>
                        </div>
                        <div class="cost-section-content">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="transport_senegal_quantity">Transport Senegal (Quantity)</label>
                                        <input type="number" class="form-control" id="transport_senegal_quantity" name="transport_senegal_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="transport_senegal_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="transport_senegal_unit_price" name="transport_senegal_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="transport_senegal_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="transport_senegal_total_cost" name="transport_senegal_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="transport_gambia_quantity">Transport Gambia (Quantity)</label>
                                        <input type="number" class="form-control" id="transport_gambia_quantity" name="transport_gambia_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="transport_gambia_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="transport_gambia_unit_price" name="transport_gambia_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="transport_gambia_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="transport_gambia_total_cost" name="transport_gambia_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services/Indirect Cost Section -->
                    <div class="cost-section">
                        <div class="cost-section-header" onclick="toggleSection(this)">
                            <h5>Services/Indirect Cost</h5>
                        </div>
                        <div class="cost-section-content">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="water_quantity">Water (Quantity)</label>
                                        <input type="number" class="form-control" id="water_quantity" name="water_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="water_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="water_unit_price" name="water_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="water_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="water_total_cost" name="water_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="electricity_quantity">Electricity (Quantity)</label>
                                        <input type="number" class="form-control" id="electricity_quantity" name="electricity_quantity" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="electricity_unit_price">Unit Price (D)</label>
                                        <input type="number" class="form-control" id="electricity_unit_price" name="electricity_unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="electricity_total_cost">Amount (D)</label>
                                        <input type="number" class="form-control" id="electricity_total_cost" name="electricity_total_cost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Other services costs -->
                            <div class="form-group">
                                <label for="maintenance_cost">Maintenance Cost (D)</label>
                                <input type="number" class="form-control" id="maintenance_cost" name="maintenance_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="rent_cost">Rent Cost (D)</label>
                                <input type="number" class="form-control" id="rent_cost" name="rent_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="refrigeration_cost">Refrigeration Cost (D)</label>
                                <input type="number" class="form-control" id="refrigeration_cost" name="refrigeration_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="marketing_cost">Marketing Cost (D)</label>
                                <input type="number" class="form-control" id="marketing_cost" name="marketing_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="medication_cost">Medication Cost (D)</label>
                                <input type="number" class="form-control" id="medication_cost" name="medication_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="hosting_cost">Hosting Cost (D)</label>
                                <input type="number" class="form-control" id="hosting_cost" name="hosting_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="electrical_installation_cost">Electrical Installation Cost (D)</label>
                                <input type="number" class="form-control" id="electrical_installation_cost" name="electrical_installation_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="business_registration_cost">Business Registration Cost (D)</label>
                                <input type="number" class="form-control" id="business_registration_cost" name="business_registration_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="insurance_cost">Insurance Cost (D)</label>
                                <input type="number" class="form-control" id="insurance_cost" name="insurance_cost" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="tax_cost">Tax Cost (D)</label>
                                <input type="number" class="form-control" id="tax_cost" name="tax_cost" step="0.01" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">Save Cost Record</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cost History Table -->
        <div class="card">
            <div class="card-header">
                <h4>Cost History</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Fish Type</th>
                                <th>Fingerlings</th>
                                <th>Feed</th>
                                <th>Materials</th>
                                <th>Transport</th>
                                <th>Services</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($costHistory as $record): ?>
                                <tr>
                                    <td><?= htmlspecialchars($record['date_recorded']) ?></td>
                                    <td><?= htmlspecialchars($record['fish_name']) ?></td>
                                    <td>D<?= number_format($record['total_fingerlings_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['total_feed_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['total_material_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['total_transport_cost'], 2) ?></td>
                                    <td>D<?= number_format($record['total_services_cost'], 2) ?></td>
                                    <td class="fw-bold">D<?= number_format($record['running_cost'], 2) ?></td>

                                </tr>


                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle section visibility
        function toggleSection(header) {
            const section = header.parentElement;
            section.classList.toggle('active');
        }

        // Calculate totals when quantity or unit price changes
        document.querySelectorAll('input[type="number"]').forEach(input => {
            if (!input.readOnly && !input.id.includes('total')) {
                input.addEventListener('input', function() {
                    const baseId = this.id.replace(/_quantity|_unit_price/, '');
                    const quantity = parseFloat(document.getElementById(`${baseId}_quantity`)?.value) || 0;
                    const unitPrice = parseFloat(document.getElementById(`${baseId}_unit_price`)?.value) || 0;
                    const total = quantity * unitPrice;

                    const totalInput = document.getElementById(`${baseId}_total_cost`);
                    if (totalInput) {
                        totalInput.value = total.toFixed(2);
                    }
                });
            }
        });

        // Initialize all sections as collapsed
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.cost-section').forEach(section => {
                section.classList.remove('active');
            });

            // Calculate totals for existing values in edit modals
            document.querySelectorAll('[id$="_quantity"], [id$="_unit_price"]').forEach(input => {
                if (input.value) {
                    const event = new Event('input');
                    input.dispatchEvent(event);
                }
            });
        });
    </script>
</body>

</html>