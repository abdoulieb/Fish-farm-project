<?php
require_once 'auth.php';
require_once 'config.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    // Get member data first
    $stmt = $pdo->prepare("SELECT photo_url FROM team_members WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $member = $stmt->fetch();

    // Delete photo if exists
    if ($member && $member['photo_url'] && file_exists($member['photo_url'])) {
        unlink($member['photo_url']);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['message'] = "Team member deleted successfully!";
}

header("Location: admin_dashboard.php");
exit();
