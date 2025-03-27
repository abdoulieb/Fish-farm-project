<?php
require_once 'config.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get all contact submissions
$stmt = $pdo->query("SELECT * FROM contact_submissions ORDER BY submitted_at DESC");
$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Contact Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 70px;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .status-badge {
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="welcome.php">
                <i class="fas fa-fish me-2"></i> Bah & Brothers
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_contacts.php">Contact Messages</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="my-4">Contact Form Submissions</h1>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= htmlspecialchars($submission['id']) ?></td>
                            <td><?= htmlspecialchars($submission['name']) ?></td>
                            <td><?= htmlspecialchars($submission['email']) ?></td>
                            <td><?= htmlspecialchars($submission['phone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars(ucfirst($submission['subject'])) ?></td>
                            <td><?= htmlspecialchars(substr($submission['message'], 0, 50)) ?>...</td>
                            <td><?= date('M j, Y g:i a', strtotime($submission['submitted_at'])) ?></td>
                            <td>
                                <a href="view_contact.php?id=<?= $submission['id'] ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>