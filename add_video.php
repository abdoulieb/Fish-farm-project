<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $videoUrl = $_POST['video_url'] ?? '';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($title) || (empty($videoUrl) && empty($_FILES['video_file']['name']))) {
        $_SESSION['error'] = "Title and either Video URL or Video File are required";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Handle file upload if provided
    $thumbnailUrl = $_POST['thumbnail_url'] ?? '';
    if (!empty($_FILES['video_file']['name'])) {
        $uploadDir = 'uploads/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['video_file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $targetPath)) {
            $videoUrl = $targetPath;
        } else {
            $_SESSION['error'] = "Failed to upload video file";
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // Handle thumbnail upload if provided
    if (!empty($_FILES['thumbnail_file']['name'])) {
        $uploadDir = 'uploads/thumbnails/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['thumbnail_file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $targetPath)) {
            $thumbnailUrl = $targetPath;
        }
    }

    if (addVideo($title, $description, $videoUrl, $thumbnailUrl, $isFeatured)) {
        $_SESSION['message'] = "Video added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add video";
    }
}

header("Location: admin_dashboard.php");
exit();
