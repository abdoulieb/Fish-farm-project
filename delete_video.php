<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: admin_dashboard.php");
    exit();
}

$videoId = $_GET['id'] ?? 0;

if (deleteVideo($videoId)) {
    $_SESSION['message'] = "Video deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete video";
}

header("Location: admin_dashboard.php");
exit();
