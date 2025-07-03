<?php
require_once 'auth.php';
require_once 'config.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['id'])) {
    // Get partner data first
    $stmt = $pdo->prepare("SELECT logo_url FROM partners WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $partner = $stmt->fetch();

    // Delete logo if exists
    if ($partner && $partner['logo_url'] && file_exists($partner['logo_url'])) {
        unlink($partner['logo_url']);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM partners WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['message'] = "Partner deleted successfully!";
}

header("Location: admin_dashboard.php");
exit();
<?php
require_once 'auth.php';
require_once 'config.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['id'])) {
    // Get partner data first
    $stmt = $pdo->prepare("SELECT logo_url FROM partners WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $partner = $stmt->fetch();

    // Delete logo if exists
    if ($partner && $partner['logo_url'] && file_exists($partner['logo_url'])) {
        unlink($partner['logo_url']);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM partners WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['message'] = "Partner deleted successfully!";
}

header("Location: admin_dashboard.php");
exit();
