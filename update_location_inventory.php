<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationId = $_POST['location_id'];
    $employeeId = $_POST['employee_id'];
    $fishQuantities = $_POST['fish'];

    try {
        $pdo->beginTransaction();

        foreach ($fishQuantities as $fishTypeId => $quantity) {
            if ($quantity > 0) {
                // Check if assignment exists
                $stmt = $pdo->prepare("SELECT id FROM location_inventory 
                                      WHERE location_id = ? AND employee_id = ? AND fish_type_id = ?");
                $stmt->execute([$locationId, $employeeId, $fishTypeId]);

                if ($stmt->fetch()) {
                    // Update existing
                    $stmt = $pdo->prepare("UPDATE location_inventory SET quantity = ? 
                                          WHERE location_id = ? AND employee_id = ? AND fish_type_id = ?");
                    $stmt->execute([$quantity, $locationId, $employeeId, $fishTypeId]);
                } else {
                    // Insert new
                    $stmt = $pdo->prepare("INSERT INTO location_inventory 
                                          (location_id, employee_id, fish_type_id, quantity) 
                                          VALUES (?, ?, ?, ?)");
                    $stmt->execute([$locationId, $employeeId, $fishTypeId, $quantity]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['message'] = "Location inventory updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating location inventory: " . $e->getMessage();
    }
}

header("Location: admin.php");
exit();
