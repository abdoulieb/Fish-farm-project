<?php
require_once 'config.php';

function registerUser($username, $password, $email)
{
    global $pdo;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $hashedPassword, $email]);
}

function loginUser($username, $password)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}


// Add these functions to auth.php
function checkEmployeePermission($permission)
{
    if (!isLoggedIn() || $_SESSION['role'] !== 'employee') return false;

    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT $permission FROM employee_permissions WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();

        return $result ? (bool)$result[$permission] : false;
    } catch (PDOException $e) {
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}

function canEmployeeSell()
{
    return checkEmployeePermission('can_sell');
}

function canEmployeeRecordFatality()
{
    return checkEmployeePermission('can_record_fatality');
}

function canEmployeeProcessOrders()
{
    return checkEmployeePermission('can_process_orders');
}
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function logout()
{
    session_destroy();
    header("Location: login.php");
    exit();
}
function isEmployee()
{
    return isLoggedIn() && $_SESSION['role'] === 'employee';
}
