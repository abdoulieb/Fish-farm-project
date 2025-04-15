<?php
require_once 'config.php';
require_once 'auth.php';

// Only allow admins to access this endpoint
if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Only admins can delete assignments']);
    exit();
}

// Check if assignment ID was provided
if (!isset($_POST['assignment_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Assignment ID is required']);
    exit();
}

$assignmentId = intval($_POST['assignment_id']);

try {
    $pdo->beginTransaction();

    // 1. Get the assignment details
    $stmt = $pdo->prepare("
        SELECT fish_type_id, quantity, employee_id 
        FROM location_inventory 
        WHERE id = ?
    ");
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        throw new Exception("Assignment not found");
    }

    $fishTypeId = $assignment['fish_type_id'];
    $quantity = $assignment['quantity'];
    $employeeId = $assignment['employee_id'];

    // 2. Return the quantity to main inventory
    $stmt = $pdo->prepare("
        UPDATE inventory 
        SET quantity_kg = quantity_kg + ? 
        WHERE fish_type_id = ?
    ");
    $stmt->execute([$quantity, $fishTypeId]);

    // 3. Delete the assignment
    $stmt = $pdo->prepare("
        DELETE FROM location_inventory 
        WHERE id = ?
    ");
    $stmt->execute([$assignmentId]);

    // 4. Log this action
    $stmt = $pdo->prepare("
        INSERT INTO inventory_logs 
        (action, fish_type_id, quantity, employee_id, admin_id, notes) 
        VALUES ('assignment_deleted', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $fishTypeId,
        $quantity,
        $employeeId,
        $_SESSION['user_id'],
        "Assignment deleted by admin"
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Assignment deleted successfully',
        'quantity_returned' => $quantity,
        'fish_type_id' => $fishTypeId
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete assignment: ' . $e->getMessage()
    ]);
}
