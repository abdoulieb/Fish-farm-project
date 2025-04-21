<?php
require_once 'config.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: admin_contacts.php');
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM contact_submissions WHERE id = ?");
$stmt->execute([$id]);
$message = $stmt->fetch();

if (!$message) {
    header('Location: admin_contacts.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-fish me-2"></i> Bah & Brothers
            </a>
            <div class="d-flex">
                <a href="admin_contacts.php" class="btn btn-outline-light me-2">Back to Messages</a>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container" style="padding-top: 80px;">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2>Message Details</h2>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>From:</h5>
                        <p><?= htmlspecialchars($message['name']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Email:</h5>
                        <p><?= htmlspecialchars($message['email']) ?></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Phone:</h5>
                        <p><?= htmlspecialchars($message['phone'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Subject:</h5>
                        <p><?= htmlspecialchars(ucfirst($message['subject'])) ?></p>
                    </div>
                </div>
                <div class="mb-3">
                    <h5>Message:</h5>
                    <div class="border p-3 rounded bg-light">
                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                    </div>
                </div>
                <div class="mb-3">
                    <h5>Submitted At:</h5>
                    <p><?= date('M j, Y g:i a', strtotime($message['submitted_at'])) ?></p>
                </div>
            </div>
            <div class="card-footer">
                <a href="admin_contacts.php" class="btn btn-primary">Back to Messages</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>