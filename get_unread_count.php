<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $count = $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status IS NULL OR status != 'responded'")->fetchColumn();
    echo json_encode(['count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}
