<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;

    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $initialQuantity = floatval($_POST['initial_quantity']);

    // Handle file upload
    $targetDir = "uploads/fish_images/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["image_paths"]["name"]);
    $targetFile = $targetDir . uniqid() . '_' . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["image_paths"]["tmp_name"]);
    if ($check === false) {
        $_SESSION['error'] = "File is not an image.";
        header("Location: admin.php");
        exit();
    }

    // Check file size (5MB max)
    if ($_FILES["image_paths"]["size"] > 5000000) {
        $_SESSION['error'] = "Sorry, your file is too large.";
        header("Location: admin.php");
        exit();
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        header("Location: admin.php");
        exit();
    }

    // Try to upload file
    if (move_uploaded_file($_FILES["image_paths"]["tmp_name"], $targetFile)) {
        try {
            $pdo->beginTransaction();

            // Add fish type with image path
            $stmt = $pdo->prepare("INSERT INTO fish_types (name, description, price_per_kg, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $targetFile]);
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
    } else {
        $_SESSION['error'] = "Sorry, there was an error uploading your file.";
    }
}

header("Location: admin.php");
exit();
