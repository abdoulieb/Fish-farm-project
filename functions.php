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
        $stmt = $pdo->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE user_id = ? ORDER BY order_date DESC");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY order_date DESC");
    }
    return $stmt->fetchAll();
}

function getOrderItems($orderId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT oi.*, f.name FROM order_items oi JOIN fish_types f ON oi.fish_type_id = f.id WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function updateOrderStatus($orderId, $status)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $orderId]);
}
