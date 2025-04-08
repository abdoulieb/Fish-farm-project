<?php
require_once 'config.php';
require_once 'auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $website = $_POST['website'] ?? null;

    // Handle file upload
    $targetDir = "uploads/partners/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["logo"]["name"]);
    $targetFile = $targetDir . uniqid() . '_' . $fileName;

    if (move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFile)) {
        $stmt = $pdo->prepare("INSERT INTO partners (name, logo_url, website) VALUES (?, ?, ?)");
        $stmt->execute([$name, $targetFile, $website]);

        $_SESSION['message'] = "Partner added successfully!";
    } else {
        $_SESSION['error'] = "Error uploading logo";
    }
}

header("Location: admin.php");
exit();
