<?php
// config.php

$host = 'localhost';
$dbname = 'fish_inventory';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
