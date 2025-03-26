<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;

    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $initialQuantity = floatval($_POST['initial_quantity']);

    try {
        $pdo->beginTransaction();

        // Add fish type
        $stmt = $pdo->prepare("INSERT INTO fish_types (name, description, price_per_kg) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $price]);
        $fishTypeId = $pdo->lastInsertId();

        // Add to inventory
        $stmt = $pdo->prepare("INSERT INTO inventory (fish_type_id, quantity_kg) VALUES (?, ?)");
        $stmt->execute([$fishTypeId, $initialQuantity]);

        $pdo->commit();
        $_SESSION['message'] = "New fish type added successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to add new fish type: " . $e->getMessage();
    }
}

header("Location: admin.php");
exit();
