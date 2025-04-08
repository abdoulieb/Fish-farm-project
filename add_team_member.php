<?php
require_once 'config.php';
require_once 'auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $position = $_POST['position'];
    $facebook = $_POST['facebook'] ?? null;
    $twitter = $_POST['twitter'] ?? null;
    $linkedin = $_POST['linkedin'] ?? null;

    // Handle file upload
    $targetDir = "uploads/team/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["photo"]["name"]);
    $targetFile = $targetDir . uniqid() . '_' . $fileName;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
        $stmt = $pdo->prepare("INSERT INTO team_members (name, position, photo_url, facebook, twitter, linkedin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $position, $targetFile, $facebook, $twitter, $linkedin]);

        $_SESSION['message'] = "Team member added successfully!";
    } else {
        $_SESSION['error'] = "Error uploading photo";
    }
}

header("Location: admin.php");
exit();
