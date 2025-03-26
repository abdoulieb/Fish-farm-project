<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    die("Unauthorized access");
}

if (!isset($_GET['order_id'])) {
    die("Order ID not specified");
}

$orderId = $_GET['order_id'];
$items = getOrderItems($orderId);
?>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Fish Type</th>
                <th>Quantity (kg)</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= number_format($item['quantity_kg'], 2) ?></td>
                    <td>D<?= number_format($item['unit_price'], 2) ?></td>
                    <td>D<?= number_format($item['quantity_kg'] * $item['unit_price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>