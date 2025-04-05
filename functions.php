<?php
require_once 'config.php';

// Fish functions
function getAllFishTypes()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM fish_types");
    return $stmt->fetchAll();
}

function getFishTypeById($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM fish_types WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateFishType($id, $name, $description, $price)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE fish_types SET name = ?, description = ?, price_per_kg = ? WHERE id = ?");
    return $stmt->execute([$name, $description, $price, $id]);
}

// Inventory functions
function getInventory()
{
    global $pdo;
    $stmt = $pdo->query("SELECT i.*, f.name, f.price_per_kg FROM inventory i JOIN fish_types f ON i.fish_type_id = f.id");
    return $stmt->fetchAll();
}

function updateInventory($fishTypeId, $quantity)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE inventory SET quantity_kg = ? WHERE fish_type_id = ?");
    return $stmt->execute([$quantity, $fishTypeId]);
}

// Order functions
function placeOrder($userId, $items)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Calculate total amount
        $total = 0;
        foreach ($items as $item) {
            $fish = getFishTypeById($item['fish_type_id']);
            if (!$fish) {
                throw new Exception("Fish type not found.");
            }
            $total += $fish['price_per_kg'] * $item['quantity'];
        }

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->execute([$userId, $total]);
        $orderId = $pdo->lastInsertId();

        // Add order items
        foreach ($items as $item) {
            $fish = getFishTypeById($item['fish_type_id']);
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, fish_type_id, quantity_kg, unit_price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['fish_type_id'], $item['quantity'], $fish['price_per_kg']]);

            // Update inventory
            $stmt = $pdo->prepare("UPDATE inventory SET quantity_kg = quantity_kg - ? WHERE fish_type_id = ?");
            $stmt->execute([$item['quantity'], $item['fish_type_id']]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getOrders($userId = null)
{
    global $pdo;
    if ($userId) {
        $stmt = $pdo->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.user_id = ? ORDER BY o.order_date DESC");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC");
    }
    return $stmt->fetchAll();
}

function getOrderItems($orderId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT oi.*, f.name FROM order_items oi JOIN fish_types f ON oi.fish_type_id = f.id WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function updateOrderStatus($orderId, $status)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $orderId]);
}
function cancelOrder($orderId)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    return $stmt->execute([$orderId]);
}

function deleteOrder($orderId) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Check if the order status is 'pending'
        $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order || $order['status'] !== 'pending') {
            throw new Exception("Only pending orders can be deleted.");
        }

        // Retrieve order items to update inventory
        $stmt = $pdo->prepare("SELECT fish_type_id, quantity_kg FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll();

        // Roll back inventory quantities
        foreach ($orderItems as $item) {
            $stmt = $pdo->prepare("UPDATE inventory SET quantity_kg = quantity_kg + ? WHERE fish_type_id = ?");
            $stmt->execute([$item['quantity_kg'], $item['fish_type_id']]);
        }

        // Delete order items
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);

        // Delete the order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
function updateOrderItem($orderId, $newQuantity, $newTotal, $userId) {
    global $pdo;
    
    // Verify the order belongs to the user
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    if (!$stmt->fetch()) {
        return false;
    }
    
    // Update the order item
    $stmt = $pdo->prepare("UPDATE order_items SET quantity_kg = ?, total_price = ? WHERE order_id = ?");
    return $stmt->execute([$newQuantity, $newTotal, $orderId]);
}   