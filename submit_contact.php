<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    try {
        $stmt = $pdo->prepare("INSERT INTO contact_submissions (name, email, phone, subject, message, submitted_at) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $phone, $subject, $message]);

        header('Location: welcome.php?contact=success');
        exit();
    } catch (PDOException $e) {
        // Log error or handle it appropriately
        header('Location: welcome.php?contact=error');
        exit();
    }
} else {
    header('Location: welcome.php');
    exit();
}
