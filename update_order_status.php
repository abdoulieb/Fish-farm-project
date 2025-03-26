<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['order_id']) && isset($_GET['status'])) {
    $orderId = $_GET['order_id'];
    $status = $_GET['status'];

    if (updateOrderStatus($orderId, $status)) {
        $_SESSION['message'] = "Order status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
}

header("Location: admin.php");
exit();
