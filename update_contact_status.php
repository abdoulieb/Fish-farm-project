<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE contact_submissions 
                              SET status = 'responded', 
                                  admin_notes = ?,
                                  responded_at = NOW()
                              WHERE id = ?");
        $stmt->execute([
            $_POST['admin_notes'] ?? null,
            $_POST['id']
        ]);

        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    } catch (PDOException $e) {
        die("Error updating contact status: " . $e->getMessage());
    }
} else {
    header('Location: admin.php');
    exit();
}
