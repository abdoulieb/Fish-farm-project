<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);

    // Handle file upload if a new image was provided
    $image_path = $_POST['current_image'] ?? ''; // Keep existing image by default

    if (!empty($_FILES['image_path']['name'])) {
        $targetDir = "uploads/fish_images/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = basename($_FILES["image_path"]["name"]);
        $targetFile = $targetDir . uniqid() . '_' . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image_path"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error'] = "File is not an image.";
            header("Location: admin.php");
            exit();
        }

        // Check file size (5MB max)
        if ($_FILES["image_path"]["size"] > 5000000) {
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
        if (move_uploaded_file($_FILES["image_path"]["tmp_name"], $targetFile)) {
            $image_path = $targetFile;
            // Optionally delete the old image file here
        } else {
            $_SESSION['error'] = "Sorry, there was an error uploading your file.";
            header("Location: admin.php");
            exit();
        }
    }

    if (updateFishType($id, $name, $description, $price, $image_path)) {
        $_SESSION['message'] = "Fish type updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update fish type.";
    }
}

header("Location: admin.php");
exit();
