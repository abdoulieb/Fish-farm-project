<?php
require_once 'config.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['assignment_id'])) {
    header("Location: location_management.php");
    exit();
}

$assignmentId = $_POST['assignment_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Get the assignment details
$stmt = $pdo->prepare("SELECT * FROM location_inventory WHERE id = ?");
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    $_SESSION['error'] = "Assignment not found";
    header("Location: location_management.php");
    exit();
}

// Verify the current user is the assigned employee
if ($_SESSION['user_id'] != $assignment['employee_id']) {
    $_SESSION['error'] = "You can only process your own assignments";
    header("Location: location_management.php");
    exit();
}

try {
    $pdo->beginTransaction();

    if ($action === 'accept') {
        // Mark as accepted
        $stmt = $pdo->prepare("
            UPDATE location_inventory 
            SET status = 'accepted',
                last_updated = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$assignmentId]);

        $_SESSION['message'] = "Assignment accepted successfully";
    } elseif ($action === 'reject') {
        // Reject and return quantity
        $stmt = $pdo->prepare("
            UPDATE location_inventory 
            SET quantity = quantity - ?,
                status = 'rejected',
                last_updated = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$assignment['last_assigned_quantity'], $assignmentId]);

        $_SESSION['message'] = "Assignment rejected. Quantity returned.";
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to process assignment: " . $e->getMessage();
}

header("Location: location_management.php");
exit();
