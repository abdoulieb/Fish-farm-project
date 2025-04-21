<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fishTypeId = $_POST['fish_type_id'];
    $quantity = floatval($_POST['quantity']);

    if (updateInventory($fishTypeId, $quantity)) {
        $_SESSION['message'] = "Inventory updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update inventory.";
    }
}

header("Location: admin.php");
exit();
