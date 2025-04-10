<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if (!canEmployeeSell()) {
    echo json_encode(['error' => 'No permission']);
    exit();
}

$count = getPendingAssignmentsCount($_SESSION['user_id']);
echo json_encode(['count' => $count]);
