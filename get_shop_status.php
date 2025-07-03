<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $shops = $pdo->query("
        SELECT id, opening_time, closing_time, is_open
        FROM shop_locations
        ORDER BY region, location_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($shops);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $shops = $pdo->query("
        SELECT id, opening_time, closing_time, is_open
        FROM shop_locations
        ORDER BY region, location_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($shops);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
