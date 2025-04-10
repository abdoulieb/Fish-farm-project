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

function updateFishType($id, $name, $description, $price, $image_paths)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE fish_types SET name = ?, description = ?, image_path = ?, price_per_kg = ? WHERE id = ?");
    return $stmt->execute([$name, $description, $image_paths, $price, $id]);
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

function cancelOrderByUser($orderId, $userId = null)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Verify the order exists and is not already cancelled
        $stmt = $pdo->prepare("SELECT user_id, status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order || $order['status'] === 'cancelled') {
            throw new Exception("Order not found or already cancelled.");
        }

        // If userId is provided, verify the order belongs to the user (for customer cancellations)
        if ($userId !== null && $order['user_id'] != $userId && !isAdmin() && !canEmployeeProcessOrders()) {
            throw new Exception("You don't have permission to cancel this order.");
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

        // Set the order total amount to zero and mark as cancelled
        $stmt = $pdo->prepare("UPDATE orders SET total_amount = 0, status = 'cancelled' WHERE id = ?");
        $stmt->execute([$orderId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function cancelSale($saleId, $employeeId = null)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Verify the sale exists and belongs to the employee (if specified)
        $sql = "SELECT * FROM sales WHERE id = ?";
        $params = [$saleId];

        if ($employeeId) {
            $sql .= " AND employee_id = ?";
            $params[] = $employeeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sale = $stmt->fetch();

        if (!$sale) {
            throw new Exception("Sale not found or not authorized to cancel.");
        }

        // Get sale items to restore inventory
        $stmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$saleId]);
        $items = $stmt->fetchAll();

        // Restore inventory
        foreach ($items as $item) {
            // Update main inventory
            $stmt = $pdo->prepare("UPDATE inventory SET quantity_kg = quantity_kg + ? WHERE fish_type_id = ?");
            $stmt->execute([$item['quantity_kg'], $item['fish_type_id']]);

            // Update employee's assigned inventory if not admin
            if ($employeeId) {
                $stmt = $pdo->prepare("
                    UPDATE location_inventory SET quantity = quantity + ? 
                    WHERE employee_id = ? AND fish_type_id = ?
                ");
                $stmt->execute([$item['quantity_kg'], $employeeId, $item['fish_type_id']]);
            }
        }

        // Delete sale items
        $stmt = $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$saleId]);

        // Delete sale
        $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
// Add these functions to functions.php

function getReconciliationById($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM cash_reconciliations WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateReconciliation($id, $physicalCash, $pettyCash)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Get the original reconciliation to calculate the difference
        $original = getReconciliationById($id);
        if (!$original) {
            throw new Exception("Reconciliation not found.");
        }

        // Calculate new values
        $deficit = $original['expected_amount'] - $physicalCash;
        $totalCash = $physicalCash + $pettyCash;

        $stmt = $pdo->prepare("
            UPDATE cash_reconciliations 
            SET physical_cash = ?, petty_cash = ?, deficit = ?, total_cash = ?
            WHERE id = ?
        ");
        $stmt->execute([$physicalCash, $pettyCash, $deficit, $totalCash, $id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
// Add to functions.php
function getPendingAssignmentsCount($employeeId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM location_inventory 
        WHERE employee_id = ? AND status = 'pending'
    ");
    $stmt->execute([$employeeId]);
    return $stmt->fetch()['count'];
}

// Add to auth.php or functions.php