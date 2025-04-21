<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['fish_type_id'])) {
    echo json_encode(['error' => 'Fish type ID not provided']);
    exit();
}

$fishTypeId = intval($_GET['fish_type_id']);

try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total_assigned FROM location_inventory WHERE fish_type_id = ?");
    $stmt->execute([$fishTypeId]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'total_assigned' => $result['total_assigned']
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}