<?php
require_once 'auth.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];

    // Handle cancellation with inventory reversion
    if ($status === 'cancelled') {
        $success = cancelOrderByUser($orderId); // Using the function we modified earlier
    } else {
        $success = updateOrderStatus($orderId, $status);
    }

    if ($success) {
        $_SESSION['message'] = "Order #$orderId status updated to " . ucfirst($status);
    } else {
        $_SESSION['error'] = "Failed to update order status";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

header("Location: orders.php");
exit();
