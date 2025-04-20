<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Validate required fields
    if (empty($_POST['name'])) {
        throw new Exception('Name is required');
    }
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Valid email is required');
    }
    if (empty($_POST['subject'])) {
        throw new Exception('Subject is required');
    }
    if (empty($_POST['message'])) {
        throw new Exception('Message is required');
    }

    // Prepare and execute SQL
    $stmt = $pdo->prepare("INSERT INTO contact_submissions 
                          (name, email, phone, subject, message) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['name'],
        $_POST['email'],
        $_POST['phone'] ?? null,
        $_POST['subject'],
        $_POST['message']
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}