<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);

    if (updateFishType($id, $name, $description, $price)) {
        $_SESSION['message'] = "Fish type updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update fish type.";
    }
}

header("Location: admin.php");
exit();
